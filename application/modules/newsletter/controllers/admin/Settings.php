<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Newsletter\Controllers\Admin;

use Modules\Newsletter\Mappers\Newsletter as NewsletterMapper;
use Modules\Newsletter\Models\Newsletter as NewsletterModel;

class Settings extends \Ilch\Controller\Admin
{
    public function init()
    {
        $this->getLayout()->addMenu
        (
            'menuNewsletter', 
            [
                [
                    'name' => 'manage',
                    'active' => false,
                    'icon' => 'fa fa-th-list',
                    'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index'])
                ],
                [
                    'name' => 'receiver',
                    'active' => true,
                    'icon' => 'fa fa-th-list',
                    'url' => $this->getLayout()->getUrl(['controller' => 'settings', 'action' => 'index'])
                ],
                [
                    'name' => 'add',
                    'active' => false,
                    'icon' => 'fa fa-plus-circle',
                    'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'treat'])
                ]
            ]
        );
    }

    public function indexAction()
    {
        $newsletterMapper = new NewsletterMapper();

        $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('settings'), ['action' => 'index']);
        
        if ($this->getRequest()->isPost()) {    
            $newsletterModel = new NewsletterModel();
            
            foreach ($this->getRequest()->getPost('check_users') as $userEmail) {           
                if ($userEmail != '') {
                    $newsletterModel->setEmail($userEmail);
                    $newsletterMapper->saveEmail($newsletterModel);
                    //$newsletterMapper->activeEmail($newsletterModel);
                }      
            }
            $this->addMessage('saveSuccess');
        }
        
        $emails = $newsletterMapper->getMail();
        $this->getView()->set('emails', $emails);
        $userList = $newsletterMapper->getSendMailUser();
        $this->getView()->set('userList', $userList);

    }   
}
