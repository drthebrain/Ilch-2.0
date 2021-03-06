<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Linkus\Controllers\Admin;

use Modules\Linkus\Mappers\Linkus as LinkusMapper;
use Modules\Linkus\Models\Linkus as LinkusModel;

class Index extends \Ilch\Controller\Admin
{
    public function init()
    {
        $items = [
            [
                'name' => 'manage',
                'active' => false,
                'icon' => 'fa fa-th-list',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'index'])
            ],
            [
                'name' => 'add',
                'active' => false,
                'icon' => 'fa fa-plus-circle',
                'url' => $this->getLayout()->getUrl(['controller' => 'index', 'action' => 'treat'])
            ],
            [
                'name' => 'settings',
                'active' => false,
                'icon' => 'fa fa-cogs',
                'url' => $this->getLayout()->getUrl(['controller' => 'settings', 'action' => 'index'])
            ]
        ];

        if ($this->getRequest()->getControllerName() == 'index' AND $this->getRequest()->getActionName() == 'treat') {
            $items[1]['active'] = true;
        } elseif ($this->getRequest()->getControllerName() == 'settings') {
            $items[2]['active'] = true;
        } else {
            $items[0]['active'] = true;
        }

        $this->getLayout()->addMenu
        (
            'menuLinkus',
            $items
        );
    }

    public function indexAction()
    {
        $linkusMapper = new LinkusMapper();

        $this->getLayout()->getAdminHmenu()
                ->add($this->getTranslator()->trans('menuLinkus'), ['action' => 'index']);

        if ($this->getRequest()->getPost('check_linkus')) {
            if ($this->getRequest()->getPost('action') == 'delete') {
                foreach ($this->getRequest()->getPost('check_linkus') as $linkusId) {
                    $linkusMapper->delete($linkusId);
                }
            }
        }

        $this->getView()->set('linkus', $linkusMapper->getLinkus());
    }

    public function treatAction()
    {
        $linkusMapper = new LinkusMapper();

        if ($this->getRequest()->getParam('id')) {
            $this->getLayout()->getAdminHmenu()
                    ->add($this->getTranslator()->trans('menuLinkus'), ['action' => 'index'])
                    ->add($this->getTranslator()->trans('edit'), ['action' => 'treat']);

            $this->getView()->set('linkus', $linkusMapper->getLinkusById($this->getRequest()->getParam('id')));
        } else {
            $this->getLayout()->getAdminHmenu()
                    ->add($this->getTranslator()->trans('menuLinkus'), ['action' => 'index'])
                    ->add($this->getTranslator()->trans('add'), ['action' => 'treat']);
        }

        if ($this->getRequest()->isPost()) {
            $model = new LinkusModel();

            if ($this->getRequest()->getParam('id')) {
                $model->setId($this->getRequest()->getParam('id'));
            }

            $title = trim($this->getRequest()->getPost('title'));
            $banner = trim($this->getRequest()->getPost('banner'));

            if (empty($title)) {
                $this->addMessage('missingTitle', 'danger');
            } elseif (empty($banner)) {
                $this->addMessage('missingBanner', 'danger');
            } else {
                $model->setTitle($title);
                $model->setBanner($banner);
                $linkusMapper->save($model);

                $this->addMessage('saveSuccess');

                $this->redirect(['action' => 'index']);
            }
        }
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $linkusMapper = new LinkusMapper();
            $linkusMapper->delete($this->getRequest()->getParam('id'));

            $this->addMessage('deleteSuccess');
        }

        $this->redirect(['action' => 'index']);
    }
}
