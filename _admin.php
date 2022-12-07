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
if (!defined('DC_CONTEXT_ADMIN')) { return; }

$core->addBehavior('adminPreferencesForm',array('notificationBehaviors','adminUserForm'));
$core->addBehavior('adminUserForm',array('notificationBehaviors','adminUserForm'));	// user.php

$core->addBehavior('adminBeforeUserUpdate',array('notificationBehaviors','adminBeforeUserUpdate'));
$core->addBehavior('adminBeforeUserOptionsUpdate',array('notificationBehaviors','adminBeforeUserUpdate'));	//preferences.php
