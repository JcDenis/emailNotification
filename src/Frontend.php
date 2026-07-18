<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Mail\Mail;
use Dotclear\Schema\Extension\User;

/**
 * @brief       emailNotification frontend class.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehavior('publicAfterCommentCreate', function (Cursor $cur, ?int $comment_id): void {
            if (!App::blog()->isDefined()) {
                return;
            }

            // We don't want notification for spam
            if (!is_numeric($cur->getField('comment_status')) || (int) $cur->getField('comment_status') == App::status()->comment()::JUNK) {
                return;
            }

            // Information on comment author and post author
            App::frontend()->context()->preview = true; //bad hack to get all comments
            $rs = App::auth()->sudo(App::blog()->getComments(...), ['comment_id' => $comment_id]);
            App::frontend()->context()->preview = false;

            if (!($rs instanceof MetaRecord) || $rs->isEmpty()) {
                return;
            }

            $sql   = new SelectStatement();
            $users = $sql->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                ->columns([
                    'U.user_id as user_id',
                    'user_email',
                    'user_options',
                ])
                ->join(
                    (new JoinStatement())
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::PERMISSIONS_TABLE_NAME, 'P'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
                )
                ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                ->union(
                    (new SelectStatement())
                    ->columns([
                        'U.user_id as user_id',
                        'user_email',
                        'user_options',
                    ])
                    ->from($sql->as(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
                    ->where('user_super = 1')
                    ->statement()
                )
                ->select();

            if (is_null($users) || $users->isEmpty()) {
                return;
            }

            // Create notify list
            $ulist = [];
            while ($users->fetch()) {
                if ($users->strField('user_email') === '') {
                    continue;
                }

                $o                 = User::options($users);
                $notification_pref = isset($o[My::id()]) ? $o[My::id()] : null;
                unset($o);

                if ($notification_pref == 'all'
                || ($notification_pref == 'mine' && $users->strField('user_id') == $rs->strField('user_id'))) {
                    $ulist[$users->strField('user_id')] = $users->strField('user_email');
                }
            }

            if (count($ulist) > 0) {
                // Author of the post wants to be notified by mail
                $headers = [
                    'Reply-To: ' . $rs->strField('comment_email'),
                    'Content-Type: text/plain; charset=UTF-8;',
                    'X-Mailer: Dotclear',
                    'X-Blog-Id: ' . Mail::B64Header(App::blog()->id()),
                    'X-Blog-Name: ' . Mail::B64Header(App::blog()->name()),
                    'X-Blog-Url: ' . Mail::B64Header(App::blog()->url()),
                ];

                $subject = '[' . App::blog()->name() . '] ' . sprintf(__('"%s" - New comment'), $rs->strField('post_title'));
                $subject = Mail::B64Header($subject);

                $msg = preg_replace('%</p>\s*<p>%msu', "\n\n", (string) $rs->strField('comment_content'));
                $msg = Html::clean($msg);
                $msg = html_entity_decode($msg);

                $msg .= "\n\n-- \n" .
                sprintf(__('Blog: %s'), App::blog()->name()) . "\n" .
                sprintf(__('Entry: %s <%s>'), $rs->strField('post_title'), $rs->getPostURL()) . "\n" .
                sprintf(__('Comment by: %s <%s>'), $rs->strField('comment_author'), $rs->strField('comment_email')) . "\n" .
                sprintf(__('Website: %s'), $rs->getAuthorURL()) . "\n" .
                sprintf(__('Comment status: %s'), __(App::status()->comment()->name((int) $cur->getField('comment_status')))) . "\n" .
                sprintf(__('Edit this comment: <%s>'), App::config()->adminUrl() .
                    (str_ends_with(App::config()->adminUrl(), '/') ? '' : '/') .
                    '?process=Comment&id=' . (is_numeric($cur->getField('comment_id')) ? $cur->getField('comment_id') : 0) .
                    '&switchblog=' . App::blog()->id()
                ) . "\n" .
                __('You must log in on the backend before clicking on this link to go directly to the comment.');

                $msg = __('You received a new comment on your blog:') . "\n\n" . $msg;

                // --BEHAVIOR-- emailNotificationAppendToEmail -- Cursor
                $msg .= App::behavior()->callBehavior('emailNotificationAppendToEmail', $cur);

                foreach ($ulist as $email) {
                    $h = array_merge(['From: ' . $email], $headers);
                    Mail::sendMail($email, $subject, $msg, $h);
                }
            }
        });

        return true;
    }
}
