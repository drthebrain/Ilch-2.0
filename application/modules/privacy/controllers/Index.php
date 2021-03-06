<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Privacy\Controllers;

use Modules\Privacy\Mappers\Privacy as PrivacyMapper;

class Index extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        $privacyMapper = new PrivacyMapper();

        $this->getLayout()->getHmenu()
                ->add($this->getTranslator()->trans('menuPrivacy'), ['action' => 'index']);

        $this->getView()->set('privacy', $privacyMapper->getPrivacy());
        $this->getView()->set('privacyShow', $privacyMapper->getPrivacy(['show' => 1]));
    }
}
