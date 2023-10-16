<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       emailNotification backend class.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Backend extends Process
{
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
            'adminPreferencesFormV2'       => BackendBehaviors::adminUserForm(...),
            'adminUserForm'                => BackendBehaviors::adminUserForm(...),
            'adminBeforeUserUpdate'        => BackendBehaviors::adminBeforeUserUpdate(...),
            'adminBeforeUserOptionsUpdate' => BackendBehaviors::adminBeforeUserUpdate(...),
        ]);

        return true;
    }
}
