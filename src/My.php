<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       emailNotification My helper.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * User preference ID of notification mode.
     *
     * @var     string  PREF_ID
     */
    public const PREF_ID = 'notify';

    /**
     * Notification modes.
     *
     * @var     array<int, string>  MODES
     */
    public const MODES = ['0', 'mine', 'all'];

    /**
     * Get notification mode of a user (0, mine or all).
     *
     * @param   string  $user_id    The user ID
     */
    public static function getUserMode(string $user_id): string
    {
        $mode = $user_id === '' ? '' : App::userPreferences()->createFromUser($user_id, self::id())->get(self::id())->get(self::PREF_ID);

        return is_string($mode) && in_array($mode, self::MODES, true) ? $mode : '0';
    }

    /**
     * Set notification mode of a user.
     *
     * @param   string  $user_id    The user ID
     * @param   string  $mode       The mode (0, mine or all)
     */
    public static function setUserMode(string $user_id, string $mode): void
    {
        if ($user_id === '' || !in_array($mode, self::MODES, true)) {
            return;
        }

        App::userPreferences()->createFromUser($user_id, self::id())->get(self::id())->put(
            self::PREF_ID,
            $mode,
            App::userWorkspace()::WS_STRING,
            'Notify new comments by email'
        );
    }
}
