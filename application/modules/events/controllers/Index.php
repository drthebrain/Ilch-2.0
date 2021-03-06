<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Events\Controllers;

use Modules\Events\Mappers\Events as EventMapper;
use Modules\Events\Models\Events as EventModel;

class Index extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        $eventMapper = new EventMapper();

        $this->getLayout()->getHmenu()
                ->add($this->getTranslator()->trans('menuEvents'), ['controller' => 'index']);

        $upcomingLimit = 5;
        $otherLimit = 5;
        $pastLimit = 5;

        $this->getView()->set('eventList', $eventMapper->getEntries());
        $this->getView()->set('eventListUpcoming', $eventMapper->getEventListUpcoming($upcomingLimit));
        $this->getView()->set('getEventListOther', $eventMapper->getEventListOther($otherLimit));
        $this->getView()->set('eventListPast', $eventMapper->getEventListPast($pastLimit));
    }

    public function treatAction()
    {
        $eventMapper = new EventMapper();
        $eventModel = new EventModel();

        $event = $eventMapper->getEventById($this->getRequest()->getParam('id'));
        if ($this->getRequest()->getParam('id')) {
            $this->getLayout()->getHmenu()->add($this->getTranslator()->trans('menuEvents'), ['action' => 'index'])
                    ->add($event->getTitle(), ['controller' => 'show', 'action' => 'event', 'id' => $event->getId()])
                    ->add($this->getTranslator()->trans('edit'), ['action' => 'treat', 'id' => $event->getId()]);

            $this->getView()->set('event', $eventMapper->getEventById($this->getRequest()->getParam('id')));
        } else {
            $this->getLayout()->getHmenu()->add($this->getTranslator()->trans('menuEvents'), ['action' => 'index'])
                    ->add($this->getTranslator()->trans('add'), ['action' => 'treat']);
        }

        $imageAllowedFiletypes = $this->getConfig()->get('event_filetypes');
        $imageHeight = $this->getConfig()->get('event_height');
        $imageWidth = $this->getConfig()->get('event_width');
        $imageSize = $this->getConfig()->get('event_size');

        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getParam('id')) {
                $eventModel->setId($this->getRequest()->getParam('id'));
                $event = $eventMapper->getEventById($this->getRequest()->getParam('id'));
            }

            $title = trim($this->getRequest()->getPost('title'));
            $start = new \Ilch\Date(trim($this->getRequest()->getPost('start')));
            $place = trim($this->getRequest()->getPost('place'));
            $text = trim($this->getRequest()->getPost('text'));
            $show = trim($this->getRequest()->getPost('calendarShow'));
            
            if ($this->getRequest()->getPost('end') != '') {
                $end = new \Ilch\Date(trim($this->getRequest()->getPost('end')));
            }

            if (!empty($_FILES['image']['name'])) {
                $path = $this->getConfig()->get('event_uploadpath');
                $file = $_FILES['image']['name'];
                $file_tmpe = $_FILES['image']['tmp_name'];
                $endung = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $file_size = $_FILES['image']['size'];

                if (in_array($endung, explode(' ', $imageAllowedFiletypes))) {
                    $size = getimagesize($file_tmpe);
                    $width = $size[0];
                    $height = $size[1];

                    if ($file_size <= $imageSize AND $width == $imageWidth AND $height == $imageHeight) {
                        $image = $path.time().'.'.$endung;

                        if ($this->getRequest()->getParam('id') AND $event->getImage() != '') {
                            $eventMapper->delImageById($this->getRequest()->getParam('id'));
                        }

                        $eventModel->setImage($image);

                        if (move_uploaded_file($file_tmpe, $image)) {
                            $this->addMessage('successImage');
                        }
                    } else {
                        $this->addMessage('failedFilesize', 'warning');
                    }
                } else {
                    $this->addMessage('failedFiletypes', 'warning');
                }
            }

            if (empty($start)) {
                $this->addMessage('missingDate', 'danger');
            } elseif(empty($title)) {
                $this->addMessage('missingTitle', 'danger');
            } elseif(empty($place)) {
                $this->addMessage('missingPlace', 'danger');
            } elseif(empty($text)) {
                $this->addMessage('missingText', 'danger');
            } else {
                if(!empty($_FILES['image']['name'])) {
                    $eventModel->setImage($image);
                }
                $eventModel->setUserId($this->getUser()->getId());
                $eventModel->setTitle($title);
                $eventModel->setStart($start);
                $eventModel->setEnd($end);
                $eventModel->setPlace($place);
                $eventModel->setText($text);
                $eventModel->setShow($show);
                $eventMapper->save($eventModel);

                $this->addMessage('saveSuccess');

                if ($this->getRequest()->getPost('image_delete') != '') {
                    $eventMapper->delImageById($this->getRequest()->getParam('id'));

                    $this->redirect(['action' => 'treat', 'id' => $this->getRequest()->getParam('id')]);
                }

                if ($this->getRequest()->getParam('id')) {
                    $eventId = $this->getRequest()->getParam('id');
                    $this->redirect(['controller' => 'show', 'action' => 'event', 'id' => $eventId]);
                } else {
                    $this->redirect(['controller' => 'show', 'action' => 'my']);
                }
            }
        }

        if ($eventMapper->existsTable('calendar') == true) {
            $this->getView()->set('calendarShow', 1);
        }

        $this->getView()->set('image_height', $imageHeight);
        $this->getView()->set('image_width', $imageWidth);
        $this->getView()->set('image_size', $imageSize);
        $this->getView()->set('image_filetypes', $imageAllowedFiletypes);
    }

    public function delAction()
    {
        if ($this->getRequest()->isSecure()) {
            $eventMapper = new EventMapper();

            $eventMapper->delete($this->getRequest()->getParam('id'));

            $this->addMessage('deleteSuccess');
        }

        $this->redirect(['controller' => 'index', 'action' => 'index']);
    }
}
