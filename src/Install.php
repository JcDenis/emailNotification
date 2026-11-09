<?php

declare(strict_types=1);

namespace Dotclear\Plugin\emailNotification;

use Dotclear\App;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief       emailNotification install class.
 * @ingroup     emailNotification
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade from version < 2.5: move users notification mode from user options to user preferences
            $current = App::version()->getVersion(My::id());
            if ($current !== '' && version_compare($current, '2.5', '<')) {
                self::moveUserOptionsToPreferences();
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        return true;
    }

    /**
     * Move users notification mode from (deprecated) user options to user preferences.
     */
    private static function moveUserOptionsToPreferences(): void
    {
        $table = App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME;

        $sql = new SelectStatement();
        $rs  = $sql
            ->columns(['user_id', 'user_options'])
            ->from($table)
            ->where($sql->like('user_options', '%' . My::id() . '%'))
            ->select();

        if (is_null($rs)) {
            return;
        }

        while ($rs->fetch()) {
            $user_id = $rs->strField('user_id');
            $options = @unserialize($rs->strField('user_options'));
            if ($user_id === '' || !is_array($options) || !array_key_exists(My::id(), $options)) {
                continue;
            }

            // Copy notification mode to user preferences
            if (is_string($options[My::id()])) {
                My::setUserMode($user_id, $options[My::id()]);
            }

            // Remove notification mode from user options
            unset($options[My::id()]);

            $upd = new UpdateStatement();
            $upd
                ->ref($table)
                ->set('user_options = ' . $upd->quote(serialize($options)))
                ->where('user_id = ' . $upd->quote($user_id))
                ->update();
        }
    }
}
