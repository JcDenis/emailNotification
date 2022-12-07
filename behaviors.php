<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of emailNotification, a plugin for Dotclear 2.
#
# Copyright (c) Olivier Meunier and contributors
# Licensed under the GPL version 2.0 license.
# See LICENSE file or
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------
if (!defined('DC_RC_PATH')) {return;}

class notificationBehaviors
{
	public static function adminUserForm($core)
	{
		global $user_options;

		$notify_comments = !empty($user_options['notify_comments']) ? $user_options['notify_comments'] : '0';

		$opt = array(
			__('Never') => '0',
			__('My entries') => 'mine',
			__('All entries') => 'all'
		);

		echo
		'<div class="fieldset"><h5>'.__('Email notification').'</h5>'.
		'<p><label class="classic" for="notify_comments">'.__('Notify new comments by email:').' '.
		form::combo('notify_comments',$opt,$notify_comments).
		'</label></p>'.
		'</div>';
	}

	public static function adminBeforeUserUpdate($cur,$user_id='')
	{
		$cur->user_options['notify_comments'] = $_POST['notify_comments'];
	}

	public static function publicAfterCommentCreate($cur,$comment_id)
	{
		# We don't want notification for spam
		if ($cur->comment_status == -2) {
			return;
		}

		global $core;

		# Information on comment author and post author
		$rs = $core->auth->sudo(array($core->blog,'getComments'), array('comment_id'=>$comment_id));

		if ($rs->isEmpty()) {
			return;
		}

		# Information on blog users
		$strReq =
		'SELECT U.user_id, user_email, user_options '.
		'FROM '.$core->blog->prefix.'user U JOIN '.$core->blog->prefix.'permissions P ON U.user_id = P.user_id '.
		"WHERE blog_id = '".$core->con->escape($core->blog->id)."' ".
		'UNION '.
		'SELECT user_id, user_email, user_options '.
		'FROM '.$core->blog->prefix.'user '.
		'WHERE user_super = 1 ';

		$users = $core->con->select($strReq);

		# Create notify list
		$ulist = array();
		while ($users->fetch()) {
			if (!$users->user_email) {
				continue;
			}

			$notification_pref = self::notificationPref(rsExtUser::options($users));

			if ($notification_pref == 'all'
			|| ($notification_pref == 'mine' && $users->user_id == $rs->user_id) )
			{
				$ulist[$users->user_id] = $users->user_email;
			}
		}

		if (count($ulist) > 0)
		{
			# Author of the post wants to be notified by mail
			$headers = array(
				'Reply-To: '.$rs->comment_email,
				'Content-Type: text/plain; charset=UTF-8;',
				'X-Mailer: Dotclear',
				'X-Blog-Id: '.mail::B64Header($core->blog->id),
				'X-Blog-Name: '.mail::B64Header($core->blog->name),
				'X-Blog-Url: '.mail::B64Header($core->blog->url)
			);

			$subject = '['.$core->blog->name.'] '.sprintf(__('"%s" - New comment'),$rs->post_title);
			$subject = mail::B64Header($subject);

			$msg = preg_replace('%</p>\s*<p>%msu',"\n\n",$rs->comment_content);
			$msg = html::clean($msg);
			$msg = html_entity_decode($msg);

			if ($cur->comment_status == 1)
			{
				$status = __('published');
			}
			elseif ($cur->comment_status == 0)
			{
				$status = __('unpublished');
			}
			elseif ($cur->comment_status == -1)
			{
				$status = __('pending');
			}
			else
			{
				# unknown status
				$status = $cur->comment_status;
			}

			$msg .= "\n\n-- \n".
			sprintf(__('Blog: %s'),$core->blog->name)."\n".
			sprintf(__('Entry: %s <%s>'),$rs->post_title,$rs->getPostURL())."\n".
			sprintf(__('Comment by: %s <%s>'),$rs->comment_author,$rs->comment_email)."\n".
			sprintf(__('Website: %s'),$rs->getAuthorURL())."\n".
			sprintf(__('Comment status: %s'),$status)."\n".
			sprintf(__('Edit this comment: <%s>'),DC_ADMIN_URL.
				((substr(DC_ADMIN_URL,-1) != '/') ? '/' : '').
				'comment.php?id='.$cur->comment_id.
				'&switchblog='.$core->blog->id)."\n".
			__('You must log in on the backend before clicking on this link to go directly to the comment.');

			$msg = __('You received a new comment on your blog:')."\n\n".$msg;

			# --BEHAVIOR-- emailNotificationAppendToEmail
			$msg .= $core->callBehavior('emailNotificationAppendToEmail',$cur);

			foreach ($ulist as $email) {
				$h = array_merge(array('From: '.$email),$headers);
				mail::sendMail($email,$subject,$msg,$h);
			}
		}
	}

	protected static function notificationPref($o)
	{
		if (is_array($o) && isset($o['notify_comments'])) {
			return $o['notify_comments'];
		}
		return null;
	}
}
