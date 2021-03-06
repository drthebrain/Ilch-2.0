<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Contact\Controllers\Admin;

use Modules\Contact\Mappers\Receiver as ReceiverMapper;
use Modules\Contact\Models\Receiver as ReceiverModel;

class Index extends \Ilch\Controller\Admin
{
    public function init()
    {
        $this->getLayout()->addMenu
        (
            'menuContact',
            [
                [
                    'name' => 'menuReceivers',
                    'active' => true,
                    'icon' => 'fa fa-th-list',
                    'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index'])
                ],
            ]
        );

        $this->getLayout()->addMenuAction
        (
            [
                'name' => 'add',
                'icon' => 'fa fa-plus-circle',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'treat'])
            ]
        );
    }

    public function indexAction()
    {
        $receiverMapper = new ReceiverMapper();

        $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('menuContact'), ['action' => 'index']);

        if ($this->getRequest()->getPost('action') == 'delete' && $this->getRequest()->getPost('check_receivers')) {
            foreach($this->getRequest()->getPost('check_receivers') as $receiveId) {
                $receiverMapper->delete($receiveId);
            }
        }

        $this->getView()->set('receivers', $receiverMapper->getReceivers());
    }

    public function deleteAction()
    {
        if($this->getRequest()->isSecure()) {
            $receiverMapper = new ReceiverMapper();
            $receiverMapper->delete($this->getRequest()->getParam('id'));
        }

        $this->redirect(['action' => 'index']);
    }

    public function treatAction()
    {
        $receiverMapper = new ReceiverMapper();

        if ($this->getRequest()->getParam('id')) {
            $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('menuContact'), ['action' => 'index'])
                ->add($this->getTranslator()->trans('edit'), ['action' => 'treat', 'id' => $this->getRequest()->getParam('id')]);

            $this->getView()->set('receiver', $receiverMapper->getReceiverById($this->getRequest()->getParam('id')));
        } else {
            $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('menuContact'), ['action' => 'index'])
                ->add($this->getTranslator()->trans('add'), ['action' => 'treat']);
        }

        if ($this->getRequest()->isPost()) {
            $name = $this->getRequest()->getPost('name');
            $email = $this->getRequest()->getPost('email');

            if (empty($name)) {
                $this->addMessage('missingName', 'danger');
            } elseif (empty($email)) {
                $this->addMessage('missingEmail', 'danger');
            } else {
                $model = new ReceiverModel();

                if ($this->getRequest()->getParam('id')) {
                    $model->setId($this->getRequest()->getParam('id'));
                }
                $model->setName($name);
                $model->setEmail($email);
                $receiverMapper->save($model);

                $this->redirect(['action' => 'index']);
            }
        }
    }
}
