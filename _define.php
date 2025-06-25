<?php
/**
 * @file
 * @brief       The plugin emailNotification definition
 * @ingroup     emailNotification
 *
 * @defgroup    emailNotification Plugin emailNotification.
 *
 * Email notification.
 *
 * @author      Olivier Meunier (author)
 * @author      Jean-Christian Denis (latest)
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Email notification',
    'Email notification',
    'Olivier Meunier and contributors',
    '2.1',
    [
        'requires'    => [['core', '2.33']],
        'settings'    => ['pref' => '#user-options.' . $this->id . '_prefs'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-06-25T22:35:12+00:00',
    ]
);
