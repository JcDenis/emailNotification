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

use cursor;
use dcAuth;
use dcCore;
use dcNsProcess;
use dcRecord;
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

        dcCore::app()->addBehavior('publicAfterCommentCreate', function (cursor $cur, ?int $comment_id): void {
            # We don't want notification for spam
            if ($cur->comment_status == -2) {
                return;
            }

            # Information on comment author and post author
            $rs = dcCore::app()->auth->sudo([dcCore::app()->blog, 'getComments'], ['comment_id' => $comment_id]);

            if ($rs->isEmpty()) {
                return;
            }

            # Information on blog users
            $strReq = 'SELECT U.user_id, user_email, user_options ' .
            'FROM ' . dcCore::app()->blog->prefix . dcAuth::USER_TABLE_NAME . ' U ' .
            'JOIN ' . dcCore::app()->blog->prefix . dcAuth::PERMISSIONS_TABLE_NAME . ' P ON U.user_id = P.user_id ' .
            "WHERE blog_id = '" . dcCore::app()->con->escapeStr(dcCore::app()->blog->id) . "' " .
            'UNION ' .
            'SELECT user_id, user_email, user_options ' .
            'FROM ' . dcCore::app()->blog->prefix . dcAuth::USER_TABLE_NAME . ' ' .
            'WHERE user_super = 1 ';

            $users = dcCore::app()->con->select($strReq);

            # Create notify list
            $ulist = [];
            while ($users->fetch()) {
                if (!$users->user_email) {
                    continue;
                }

                $o                 = rsExtUser::options(new dcRecord($users));
                $notification_pref = is_array($o) && isset($o['notify_comments']) ? $o['notify_comments'] : null;
                unset($o);

                if ($notification_pref == 'all'
                || ($notification_pref == 'mine' && $users->user_id == $rs->user_id)) {
                    $ulist[$users->user_id] = $users->user_email;
                }
            }

            if (count($ulist) > 0) {
                # Author of the post wants to be notified by mail
                $headers = [
                    'Reply-To: ' . $rs->comment_email,
                    'Content-Type: text/plain; charset=UTF-8;',
                    'X-Mailer: Dotclear',
                    'X-Blog-Id: ' . Mail::B64Header(dcCore::app()->blog->id),
                    'X-Blog-Name: ' . Mail::B64Header(dcCore::app()->blog->name),
                    'X-Blog-Url: ' . Mail::B64Header(dcCore::app()->blog->url),
                ];

                $subject = '[' . dcCore::app()->blog->name . '] ' . sprintf(__('"%s" - New comment'), $rs->post_title);
                $subject = Mail::B64Header($subject);

                $msg = preg_replace('%</p>\s*<p>%msu', "\n\n", $rs->comment_content);
                $msg = Html::clean($msg);
                $msg = html_entity_decode($msg);

                if ($cur->comment_status == 1) {
                    $status = __('published');
                } elseif ($cur->comment_status == 0) {
                    $status = __('unpublished');
                } elseif ($cur->comment_status == -1) {
                    $status = __('pending');
                } else {
                    # unknown status
                    $status = $cur->comment_status;
                }

                $msg .= "\n\n-- \n" .
                sprintf(__('Blog: %s'), dcCore::app()->blog->name) . "\n" .
                sprintf(__('Entry: %s <%s>'), $rs->post_title, $rs->getPostURL()) . "\n" .
                sprintf(__('Comment by: %s <%s>'), $rs->comment_author, $rs->comment_email) . "\n" .
                sprintf(__('Website: %s'), $rs->getAuthorURL()) . "\n" .
                sprintf(__('Comment status: %s'), $status) . "\n" .
                sprintf(__('Edit this comment: <%s>'), DC_ADMIN_URL .
                    ((substr(DC_ADMIN_URL, -1) != '/') ? '/' : '') .
                    'comment.php?id=' . $cur->comment_id .
                    '&switchblog=' . dcCore::app()->blog->id) . "\n" .
                __('You must log in on the backend before clicking on this link to go directly to the comment.');

                $msg = __('You received a new comment on your blog:') . "\n\n" . $msg;

                # --BEHAVIOR-- emailNotificationAppendToEmail
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