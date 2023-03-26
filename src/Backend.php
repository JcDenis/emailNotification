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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

dcCore::app()->addBehavior('adminPreferencesFormV2', ['emailNotificationBehaviors', 'adminUserForm']);
dcCore::app()->addBehavior('adminUserForm', ['emailNotificationBehaviors', 'adminUserForm']);
dcCore::app()->addBehavior('adminBeforeUserUpdate', ['emailNotificationBehaviors', 'adminBeforeUserUpdate']);
dcCore::app()->addBehavior('adminBeforeUserOptionsUpdate', ['emailNotificationBehaviors', 'adminBeforeUserUpdate']);
