<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Newsletter\Controllers;

use Modules\Newsletter\Mappers\Newsletter as NewsletterMapper;
use Modules\User\Controllers\Base as BaseController;

class Index extends BaseController
{
    public function indexAction()
    {
        $newsletterMapper = new NewsletterMapper();

        $this->getLayout()->getHmenu()->add($this->getTranslator()->trans('menuNewsletter'), ['action' => 'index']);

        if ($this->getRequest()->getPost('saveNewsletter')) {
            $countEmails = $newsletterMapper->countEmails($this->getRequest()->getPost('email'));
            if ($countEmails == 0) {
                $newsletterModel = new \Modules\Newsletter\Models\Newsletter();
                $newsletterModel->setEmail($this->getRequest()->getPost('email'));
                $newsletterMapper->saveEmail($newsletterModel);

                $this->addMessage('subscribeSuccess');
            } else {
                $newsletterMapper->deleteEmail($this->getRequest()->getPost('email'));

                $this->addMessage('unsubscribeSuccess');
            }
        }

        $this->getView();
    }

    public function showAction()
    {
        if (file_exists(APPLICATION_PATH.'/layouts/'.$this->getConfig()->get('default_layout').'/views/modules/newsletter/layouts/show.php')) {
            $this->getLayout()->setFile('layouts/'.$this->getConfig()->get('default_layout').'/views/modules/newsletter/layouts/show');
        } else {
            $this->getLayout()->setFile('modules/newsletter/layouts/show');
        }

        $newsletterMapper = new NewsletterMapper();

        $newsletter = $newsletterMapper->getNewsletterById($this->getRequest()->getParam('id'));
        if ($newsletter != '') {
            $this->getView()->set('newsletter', $newsletter);            
        } else {
            $this->redirect(['action' => 'index']);            
        }
    }

    public function unsubscribeAction()
    {
        $newsletterMapper = new NewsletterMapper();

        $countEmail = $newsletterMapper->countEmails($this->getRequest()->getParam('email'));
        if ($countEmail == 1) {
            $newsletterMapper->deleteEmail($this->getRequest()->getParam('email'));

            $this->addMessage('unsubscribeSuccess');
        }

        $this->redirect(['action' => 'index']);
    }

    public function settingsAction()
    {
        $newsletterMapper = new NewsletterMapper();

        $this->getLayout()->getHmenu()
                ->add($this->getTranslator()->trans('menuPanel'), ['module' => 'user', 'controller' => 'panel', 'action' => 'index'])
                ->add($this->getTranslator()->trans('menuSettings'), ['module' => 'user', 'controller' => 'panel', 'action' => 'settings'])
                ->add($this->getTranslator()->trans('menuNewsletter'), ['controller' => 'index', 'action' => 'settings']);

        if ($this->getRequest()->isPost()) {
            $newsletterModel = new \Modules\Newsletter\Models\Newsletter();
            $newsletterModel->setId($this->getUser()->getId());
            $newsletterModel->setNewsletter($this->getRequest()->getPost('opt_newsletter'));
            $newsletterMapper->saveUserEmail($newsletterModel);

            $this->redirect(['action' => 'settings']);
        }

        $this->getView()->set('countMail', $newsletterMapper->countEmails($this->getUser()->getEmail()));
    }
}
