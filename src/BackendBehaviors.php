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
use dcCore;
use Dotclear\Helper\Html\Form\{
    Label,
    Para,
    Select
};

class BackendBehaviors
{
    public static function adminUserForm(): void
    {
        $options = dcCore::app()->auth->getOptions();

        echo
        '<div class="fieldset"><h5>' . __('Email notification') . '</h5>' .
        (new Para())->items([
            (new Label(__('Notify new comments by email:')))->for('notify_comments'),
            (new Select('notify_comments'))->default($options['notify_comments'] ?? '0')->items([
                __('Never')       => '0',
                __('My entries')  => 'mine',
                __('All entries') => 'all',
            ]),
        ])->render() .
        '</div>';
    }

    public static function adminBeforeUserUpdate(cursor $cur, string $user_id = ''): void
    {
        $cur->user_options['notify_comments'] = $_POST['notify_comments'];
    }
}