<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\{
    Div,
    Fieldset,
    Label,
    Legend,
    Para,
    Select,
    Text
};
use Dotclear\Schema\Extension\User;

/**
 * @brief       emailNotification backend behaviors.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    public static function preferencesForm(): void
    {
        echo (new Fieldset())
            ->id(My::id() . '_prefs')
            ->legend(new Legend(My::name()))
            ->fields([
                self::formForm(App::auth()->getOption(My::id())),
            ])->render();
    }

    public static function userForm(?MetaRecord $rs): void
    {
        echo self::formForm(is_null($rs)  || $rs->isEmpty() ? '0' : User::option($rs, My::id()))->render();
    }

    public static function formForm(?string $option): Para
    {
        return (new Para())->items([
            (new Label(__('Notify new comments by email:')))->for(My::id()),
            (new Select(My::id()))->default($option ?? '0')->items([
                __('Never')       => '0',
                __('My entries')  => 'mine',
                __('All entries') => 'all',
            ]),
        ]);
    }

    public static function updateUser(Cursor $cur, string $user_id = ''): void
    {
        $opt           = $cur->getField('user_options');
        $opt           = is_null($opt) ? [] : $opt;
        $opt[My::id()] = $_POST[My::id()];
        $cur->setField('user_options', $opt);
    }
}
