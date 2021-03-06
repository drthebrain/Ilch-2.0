<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Error\Config;

class Config extends \Ilch\Config\Install
{
    public $config =
        [
        'key' => 'error',
        'author' => 'Stantin, Thomas',
        'system_module' => true,
        'icon_small' => 'fa-exclamation-triangle',
        'languages' =>
            [
            'de_DE' =>
                [
                'name' => 'Error',
                'description' => 'Hier kannst du die Fehlerseiten verwalten.',
                ],
            'en_EN' =>
                [
                'name' => 'Error',
                'description' => 'Here you can manage the error-pages.',
                ],
            ]
        ];

    public function install()
    {
    }

    public function getInstallSql()
    {
    }
}
