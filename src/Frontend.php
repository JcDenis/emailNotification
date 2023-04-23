<?php
/**
 * @brief emailNotification, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Olivier Meunier and contributors
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use dcAuth;
use dcBlog;
use dcCore;
use dcNsProcess;
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Database\Statement\{
    JoinStatement,
    SelectStatement
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Mail\Mail;
use rsExtUser;

class Frontend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_RC_PATH');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        dcCore::app()->addBehavior('publicAfterCommentCreate', function (Cursor $cur, ?int $comment_id): void {
            // nullsafe PHP < 8.0
            if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog)) {
                return;
            }

            // We don't want notification for spam
            if ((int) $cur->getField('comment_status') == dcBlog::COMMENT_JUNK) {
                return;
            }

            // Information on comment author and post author
            $rs = dcCore::app()->auth->sudo([dcCore::app()->blog, 'getComments'], ['comment_id' => $comment_id]);
            if (is_null($rs) || $rs->isEmpty()) {
                return;
            }

            $sql   = new SelectStatement();
            $users = $sql->from($sql->as(dcCore::app()->blog->prefix . dcAuth::USER_TABLE_NAME, 'U'))
                ->columns([
                    'U.user_id as user_id',
                    'user_email',
                    'user_options',
                ])
                ->join(
                    (new JoinStatement())
                    ->from($sql->as(dcCore::app()->blog->prefix . dcAuth::PERMISSIONS_TABLE_NAME, 'P'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
                )
                ->where('blog_id = ' . $sql->quote(dcCore::app()->blog->id))
                ->union(
                    (new SelectStatement())
                    ->columns([
                        'U.user_id as user_id',
                        'user_email',
                        'user_options',
                    ])
                    ->from($sql->as(dcCore::app()->blog->prefix . dcAuth::USER_TABLE_NAME, 'U'))
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
                if (!$users->f('user_email')) {
                    continue;
                }

                $o                 = rsExtUser::options($users);
                $notification_pref = is_array($o) && isset($o['notify_comments']) ? $o['notify_comments'] : null;
                unset($o);

                if ($notification_pref == 'all'
                || ($notification_pref == 'mine' && $users->f('user_id') == $rs->f('user_id'))) {
                    $ulist[$users->f('user_id')] = $users->f('user_email');
                }
            }

            if (count($ulist) > 0) {
                // Author of the post wants to be notified by mail
                $headers = [
                    'Reply-To: ' . $rs->f('comment_email'),
                    'Content-Type: text/plain; charset=UTF-8;',
                    'X-Mailer: Dotclear',
                    'X-Blog-Id: ' . Mail::B64Header(dcCore::app()->blog->id),
                    'X-Blog-Name: ' . Mail::B64Header(dcCore::app()->blog->name),
                    'X-Blog-Url: ' . Mail::B64Header(dcCore::app()->blog->url),
                ];

                $subject = '[' . dcCore::app()->blog->name . '] ' . sprintf(__('"%s" - New comment'), $rs->f('post_title'));
                $subject = Mail::B64Header($subject);

                $msg = preg_replace('%</p>\s*<p>%msu', "\n\n", $rs->f('comment_content'));
                $msg = Html::clean($msg);
                $msg = html_entity_decode($msg);

                if ((int) $cur->getField('comment_status') == dcBlog::COMMENT_PUBLISHED) {
                    $status = __('published');
                } elseif ((int) $cur->getField('comment_status') == dcBlog::COMMENT_UNPUBLISHED) {
                    $status = __('unpublished');
                } elseif ((int) $cur->getField('comment_status') == dcBlog::COMMENT_PENDING) {
                    $status = __('pending');
                } else {
                    // unknown status
                    $status = $cur->getField('comment_status');
                }

                $msg .= "\n\n-- \n" .
                sprintf(__('Blog: %s'), dcCore::app()->blog->name) . "\n" .
                sprintf(__('Entry: %s <%s>'), $rs->f('post_title'), $rs->getPostURL()) . "\n" .
                sprintf(__('Comment by: %s <%s>'), $rs->f('comment_author'), $rs->f('comment_email')) . "\n" .
                sprintf(__('Website: %s'), $rs->getAuthorURL()) . "\n" .
                sprintf(__('Comment status: %s'), $status) . "\n" .
                sprintf(__('Edit this comment: <%s>'), DC_ADMIN_URL .
                    ((substr(DC_ADMIN_URL, -1) != '/') ? '/' : '') .
                    'comment.php?id=' . $cur->getField('comment_id') .
                    '&switchblog=' . dcCore::app()->blog->id) . "\n" .
                __('You must log in on the backend before clicking on this link to go directly to the comment.');

                $msg = __('You received a new comment on your blog:') . "\n\n" . $msg;

                // --BEHAVIOR-- emailNotificationAppendToEmail -- Cursor
                $msg .= dcCore::app()->callBehavior('emailNotificationAppendToEmail', $cur);

                foreach ($ulist as $email) {
                    $h = array_merge(['From: ' . $email], $headers);
                    Mail::sendMail($email, $subject, $msg, $h);
                }
            }
        });

        return true;
    }
}
