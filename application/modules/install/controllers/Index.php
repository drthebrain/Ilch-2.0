<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Install\Controllers;

class Index extends \Ilch\Controller\Frontend
{
    public function init()
    {
        $this->getLayout()->setFile('modules/install/layouts/index');

        /*
         * Dont set a time limit for installer.
         */
        @set_time_limit(0);

        $menu =
            [
            'index' =>
                [
                'langKey' => 'menuWelcomeAndLanguage'
                ],
            'license' =>
                [
                'langKey' => 'menuLicence'
                ],
            'systemcheck' =>
                [
                'langKey' => 'menuSystemCheck'
                ],
            'database' =>
                [
                'langKey' => 'menuDatabase'
                ],
            'config' =>
                [
                'langKey' => 'menuConfig'
                ],
            'finish' =>
                [
                'langKey' => 'menuFinish'
                ],
            ];

        foreach ($menu as $key => $values) {
            if ($this->getRequest()->getActionName() === $key) {
                break;
            }

            $menu[$key]['done'] = true;
        }

        $this->getLayout()->set('menu', $menu);
    }

    public function indexAction()
    {
        $this->getView()->set('languages', $this->getTranslator()->getLocaleList());
        $local = $this->getRequest()->getParam('language');

        if ($local) {
            $this->getTranslator()->setLocale($local);
            $_SESSION['language'] = $local;
            $this->redirect(['action' => 'index']);
        }

        if ($this->getRequest()->isPost()) {
            $_SESSION['install']['timezone'] = $this->getRequest()->getPost('timezone');
            $this->redirect(['action' => 'license']);
        }

        if (!empty($_SESSION['install']['timezone'])) {
            $this->getView()->set('timezone', $_SESSION['install']['timezone']);
        } else {
            $this->getView()->set('timezone', SERVER_TIMEZONE);
        }

        $this->getView()->set('timezones', \DateTimeZone::listIdentifiers());
    }

    public function licenseAction()
    {
        $this->getView()->set('licenceText', file_get_contents(APPLICATION_PATH.'/../licence.txt'));

        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getPost('licenceAccepted')) {
                $this->redirect(['action' => 'systemcheck']);
            } else {
                $this->getView()->set('error', true);
            }
        }
    }

    public function systemcheckAction()
    {
        $errors = [];
        $this->getView()->set('phpVersion', phpversion());

        if (!version_compare(phpversion(), '5.4.0', '>=')) {
            $errors['version'] = true;
        }

        if (!is_writable(CONFIG_PATH)) {
            $errors['writableConfig'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/../updates/')) {
            $errors['writableUpdates'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/../.htaccess')) {
            $errors['writableHtaccess'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/modules/media/static/upload/')) {
             $errors['writableMedia'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/modules/user/static/upload/avatar/')) {
             $errors['writableAvatar'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/modules/user/static/upload/gallery/')) {
             $errors['writableAvatar'] = true;
        }

        if (!is_writable(APPLICATION_PATH.'/../certificate/')) {
             $errors['writableCertificate'] = true;
        }

        if(!extension_loaded('openssl')) {
            $errors['opensslExtensionMissing'] = true;
            $errors['expiredCertUnknown'] = true;
        }

        if (file_exists(APPLICATION_PATH.'/../certificate/Certificate.crt')) {
            if(!array_key_exists('opensslExtensionMissing', $errors)) {
                 $public_key = file_get_contents(APPLICATION_PATH.'/../certificate/Certificate.crt');
                 $certinfo = openssl_x509_parse($public_key);
                 $validTo = $certinfo['validTo_time_t'];
                 if ($validTo < time()) {
                     $errors['expiredCert'] = true;
                 }
            }
        } else {
             $errors['missingCert'] = true;
        }

        if ($this->getRequest()->isPost() && empty($errors)) {
            $this->redirect(['action' => 'database']);
        }
    }

    public function databaseAction()
    {
        $errors = [];

        if ($this->getRequest()->isPost()) {
            $_SESSION['install']['dbEngine'] = $this->getRequest()->getPost('dbEngine');
            $_SESSION['install']['dbHost'] = $this->getRequest()->getPost('dbHost');
            $_SESSION['install']['dbUser'] = $this->getRequest()->getPost('dbUser');
            $_SESSION['install']['dbPassword'] = $this->getRequest()->getPost('dbPassword');
            $_SESSION['install']['dbName'] = $this->getRequest()->getPost('dbName');
            $_SESSION['install']['dbPrefix'] = $this->getRequest()->getPost('dbPrefix');

            $ilch = new \Ilch\Database\Factory();
            $db = $ilch->getInstanceByEngine($this->getRequest()->getPost('dbEngine'));
            $hostParts = explode(':', $this->getRequest()->getPost('dbHost'));
            $port = null;

            if (!empty($hostParts[1])) {
                $port = $hostParts[1];
            }

            try {
                $db->connect(
                    reset($hostParts),
                    $this->getRequest()->getPost('dbUser'),
                    $this->getRequest()->getPost('dbPassword'),
                    $port
                );
            } catch (\RuntimeException $ex) {
                $errors['dbConnection'] = 'dbConnectionError';
            }

            if (!$db->setDatabase($this->getRequest()->getPost('dbName'))) {
                $errors['dbDatabase'] = 'dbDatabaseError';
            }

            if (empty($errors)) {
                $this->redirect(['action' => 'config']);
            }

            $this->getView()->set('errors', $errors);
        }

        foreach (['dbHost', 'dbUser', 'dbPassword', 'dbName', 'dbPrefix'] as $name) {
            if (!empty($_SESSION['install'][$name])) {
                $this->getView()->set($name, $_SESSION['install'][$name]);
            }
        }
    }

    public function configAction()
    {
        $errors = [];

        if ($this->getRequest()->isPost()) {
            $_SESSION['install']['usage'] = $this->getRequest()->getPost('usage');
            $_SESSION['install']['modulesToInstall'][$_SESSION['install']['usage']] = $this->getRequest()->getPost('modulesToInstall');
            $_SESSION['install']['adminName'] = $this->getRequest()->getPost('adminName');
            $_SESSION['install']['adminPassword'] = $this->getRequest()->getPost('adminPassword');
            $_SESSION['install']['adminPassword2'] = $this->getRequest()->getPost('adminPassword2');
            $_SESSION['install']['adminEmail'] = $this->getRequest()->getPost('adminEmail');

            if (empty($_SESSION['install']['adminName'])) {
                $errors['adminName'] = 'fieldEmpty';
            }

            if (empty($_SESSION['install']['adminPassword'])) {
                $errors['adminPassword'] = 'fieldEmpty';
            }

            if ($_SESSION['install']['adminPassword'] !== $_SESSION['install']['adminPassword2']) {
                $errors['adminPassword2'] = 'fieldDiffersPassword';
            }

            if (empty($_SESSION['install']['adminEmail'])) {
                $errors['adminEmail'] = 'fieldEmpty';
            } elseif (!filter_var($_SESSION['install']['adminEmail'], FILTER_VALIDATE_EMAIL)) {
                $errors['adminEmail'] = 'fieldEmail';
            }

            if (empty($errors)) {
                /*
                 * Write install config.
                 */
                $fileConfig = new \Ilch\Config\File();
                $fileConfig->set('dbEngine', $_SESSION['install']['dbEngine']);
                $fileConfig->set('dbHost', $_SESSION['install']['dbHost']);
                $fileConfig->set('dbUser', $_SESSION['install']['dbUser']);
                $fileConfig->set('dbPassword', $_SESSION['install']['dbPassword']);
                $fileConfig->set('dbName', $_SESSION['install']['dbName']);
                $fileConfig->set('dbPrefix', $_SESSION['install']['dbPrefix']);
                $fileConfig->saveConfigToFile(CONFIG_PATH.'/config.php');

                /*
                 * Initialize install database.
                 */
                $dbFactory = new \Ilch\Database\Factory();
                $db = $dbFactory->getInstanceByConfig($fileConfig);
                \Ilch\Registry::set('db', $db);

                $modulesToInstall = $_SESSION['install']['modulesToInstall'][$_SESSION['install']['usage']];
                if (!empty($modulesToInstall)) {
                    $modulesToInstall = array_merge(['admin', 'article', 'user', 'page', 'media', 'comment', 'imprint', 'contact', 'privacy', 'statistic', 'cookieconsent'], $modulesToInstall);
                } else {
                    $modulesToInstall = ['admin', 'article', 'user', 'page', 'media', 'comment', 'imprint', 'contact', 'privacy', 'statistic', 'cookieconsent'];
                }

                $moduleMapper = new \Modules\Admin\Mappers\Module();

                /*
                 * Clear old tables.
                 */
                $db->dropTablesByPrefix($db->getPrefix());

                foreach ($modulesToInstall as $module) {
                    $configClass = '\\Modules\\'.ucfirst($module).'\\Config\\config';
                    $config = new $configClass($this->getTranslator());
                    $config->install();

                    if (!empty($config->config)) {
                        $moduleModel = new \Modules\Admin\Models\Module();
                        $moduleModel->setKey($config->config['key']);

                        if (isset($config->config['author'])) {
                            $moduleModel->setAuthor($config->config['author']);
                        }

                        if (isset($config->config['languages'])) {
                            foreach ($config->config['languages'] as $key => $value) {
                                $moduleModel->addContent($key, $value);
                            }
                        }

                        if (isset($config->config['system_module'])) {
                            $moduleModel->setSystemModule(true);
                        }

                        $moduleModel->setIconSmall($config->config['icon_small']);
                        $moduleMapper->save($moduleModel);
                    }
                }

                $menuMapper = new \Modules\Admin\Mappers\Menu();
                $menu1 = new \Modules\Admin\Models\Menu();
                $menu1->setId(1);
                $menu1->setTitle('Hauptmenü');
                $menuMapper->save($menu1);

                $menu2 = new \Modules\Admin\Models\Menu();
                $menu2->setId(2);
                $menu2->setTitle('Hauptmenü 2');
                $menuMapper->save($menu2);

                $sort = 0;
                $menuItem = new \Modules\Admin\Models\MenuItem();
                $menuItem->setMenuId(1);
                $menuItem->setParentId(0);
                $menuItem->setTitle('Menü');
                $menuItem->setType(0);
                $menuMapper->saveItem($menuItem);

                /*
                 * Will not linked in menu
                 */
                foreach ($modulesToInstall as $module) {
                    if (in_array($module, ['comment', 'shoutbox', 'admin', 'media', 'page', 'newsletter', 'statistic', 'cookieconsent', 'error'])) {
                        continue;
                    }

                    $configClass = '\\Modules\\'.ucfirst($module).'\\Config\\config';
                    $config = new $configClass($this->getTranslator());

                    $menuItem = new \Modules\Admin\Models\MenuItem();
                    $menuItem->setMenuId(1);
                    $menuItem->setSort($sort);
                    $menuItem->setParentId(1);
                    $menuItem->setType(3);
                    $menuItem->setModuleKey($config->config['key']);
                    $menuItem->setTitle($config->config['languages'][$this->getTranslator()->getLocale()]['name']);
                    $menuMapper->saveItem($menuItem);
                    $sort += 10;
                }

               $boxes = "INSERT INTO `[prefix]_menu_items` (`menu_id`, `sort`, `parent_id`, `page_id`, `box_id`, `box_key`, `type`, `title`, `href`, `module_key`) VALUES
                        (1, 80, 0, 0, 0, 'user_login', 4, 'Login', '', ''),
                        (1, 90, 0, 0, 0, 'admin_layoutswitch', 4, 'Layout', '', ''),
                        (1, 100, 0, 0, 0, 'statistic_stats', 4, 'Statistik', '', ''),
                        (1, 110, 0, 0, 0, 'statistic_online', 4, 'Online', '', ''),
                        (2, 10, 0, 0, 0, 'admin_langswitch', 4, 'Sprache', '', ''),
                        (2, 20, 0, 0, 0, 'article_article', 4, 'Letzte Artikel', '', ''),
                        (2, 30, 0, 0, 0, 'article_categories', 4, 'Kategorien', '', ''),
                        (2, 40, 0, 0, 0, 'article_archive', 4, 'Archive', '', '')";
                $db->queryMulti($boxes);

                unset($_SESSION['install']);
                $this->redirect(['action' => 'finish']);
            }

            $this->getView()->set('errors', $errors);
        }

        foreach (['modulesToInstall', 'usage', 'adminName', 'adminPassword', 'adminPassword2', 'adminEmail'] as $name) {
            if (!empty($_SESSION['install'][$name])) {
                $this->getView()->set($name, $_SESSION['install'][$name]);
            }
        }
    }

    public function ajaxconfigAction()
    {
        $type = $this->getRequest()->getParam('type');
        $this->getRequest()->setIsAjax(true);
        $modules = [];

        /*
         * System-Modules
         */
        $modules['user']['types']          = [];
        $modules['article']['types']       = [];
        $modules['page']['types']          = [];
        $modules['media']['types']         = [];
        $modules['comment']['types']       = [];
        $modules['contact']['types']       = [];
        $modules['imprint']['types']       = [];
        $modules['privacy']['types']       = [];
        $modules['cookieconsent']['types'] = [];
        $modules['statistic']['types']     = [];

        /*
         * Optional-Modules.
         */
        $modules['checkout']['types']   = ['clan'];
        $modules['war']['types']        = ['clan'];
        $modules['history']['types']    = ['clan'];
        $modules['rule']['types']       = ['clan'];
        $modules['training']['types']   = ['clan'];
        $modules['forum']['types']      = ['clan', 'private'];
        $modules['guestbook']['types']  = ['clan', 'private'];
        $modules['link']['types']       = ['clan', 'private'];
        $modules['linkus']['types']     = ['clan', 'private'];
        $modules['partner']['types']    = ['clan', 'private'];
        $modules['shoutbox']['types']   = ['clan', 'private'];
        $modules['gallery']['types']    = ['clan', 'private'];
        $modules['downloads']['types']  = ['clan', 'private'];
        $modules['newsletter']['types'] = ['clan', 'private'];
        $modules['birthday']['types']   = ['clan', 'private'];
        $modules['events']['types']     = ['clan', 'private'];
        $modules['calendar']['types']   = ['clan', 'private'];
        $modules['away']['types']       = ['clan', 'private'];
        $modules['awards']['types']     = ['clan', 'private'];
        $modules['jobs']['types']       = ['clan', 'private'];
        $modules['faq']['types']        = ['clan', 'private'];

        foreach ($modules as $key => $module) {
            $configClass = '\\Modules\\'.ucfirst($key).'\\Config\\config';
            $config = new $configClass($this->getTranslator());
            $modules[$key]['config'] = $config;

            if(in_array($type, $module['types']))
            {
               $modules[$key]['checked'] = true;
            }
        }

        $modulesToInstall = [];

        if(!empty($_SESSION['install']['modulesToInstall'][$type]))
        {
            $modulesToInstall = $_SESSION['install']['modulesToInstall'][$type];
        }

        $this->getView()->set('modulesToInstall', $modulesToInstall);
        $this->getView()->set('modules', $modules);
    }

    public function finishAction()
    {
    }
}
