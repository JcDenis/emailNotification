<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       emailNotification backend class.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        App::behavior()->addBehaviors([
            'adminPreferencesFormV2'       => BackendBehaviors::preferencesForm(...),
            'adminUserForm'                => BackendBehaviors::userForm(...),
            'adminBeforeUserCreate'        => BackendBehaviors::updateUser(...),
            'adminBeforeUserUpdate'        => BackendBehaviors::updateUser(...),
            'adminBeforeUserOptionsUpdate' => BackendBehaviors::updateUser(...),
        ]);

        return true;
    }
}
