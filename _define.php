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
if (!defined('DC_RC_PATH')) { return; }

$this->registerModule(
	/* Name */			"Email notification",
	/* Description*/		"Email notification",
	/* Author */			"Olivier Meunier",
	/* Version */			'1.1',
	/* Properties */
	array(
		'permissions' => 'usage,contentadmin',
		'type' => 'plugin',
		'dc_min' => '2.6',
		'support' => 'http://forum.dotclear.org/viewforum.php?id=16',
		'details' => 'http://plugins.dotaddict.org/dc2/details/emailNotification'
		)
);
