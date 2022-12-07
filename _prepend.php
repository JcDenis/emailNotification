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

$GLOBALS['__autoload']['notificationBehaviors'] = dirname(__FILE__).'/behaviors.php';
