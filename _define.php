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
    '2.0.1',
    [
        'requires'    => [['core', '2.33']],
        'settings'    => ['pref' => '#user-options.' . basename(__DIR__) . '_prefs'],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://github.com/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://github.com/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
