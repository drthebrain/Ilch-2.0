<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\User\Controllers;

use Modules\User\Mappers\User as UserMapper;
use Modules\User\Mappers\AuthToken as AuthTokenMapper;
use Modules\User\Mappers\CookieStolen as CookieStolenMapper;
use Modules\User\Models\AuthToken as AuthTokenModel;
use Modules\User\Service\Password as PasswordService;
use Modules\User\Service\Login as LoginService;

class Login extends \Ilch\Controller\Frontend
{
    public function indexAction()
    {
        $this->getLayout()->getHmenu()->add($this->getTranslator()->trans('menuLogin'), ['action' => 'index']);

        $errors = [];
        $redirectUrl = '';

        if ($this->getRequest()->isPost()) {
            if (\Ilch\Registry::get('user')) {
                $errors['alreadyLoggedIn'] = 'alreadyLoggedIn';
            }

            $emailName = $this->getRequest()->getPost('login_emailname');
            $password = $this->getRequest()->getPost('login_password');
            $redirectUrl = $this->getRequest()->getPost('login_redirect_url');
            $rememberMe = $this->getRequest()->getPost('rememberMe');

            if (empty($emailName)) {
                $errors['login_emailname'] = 'fieldEmpty';
            } elseif (empty($password)) {
                $errors['login_password'] = 'fieldEmpty';
            } else {
                $result  = LoginService::factory()->perform($emailName, $password);

                if ($result->isSuccessful()) {
                    $cookieStolenMapper = new CookieStolenMapper();

                    if(!$cookieStolenMapper->containsCookieStolen($result->getUser()->getId())) {
                        $this->addMessage($this->getTranslator()->trans('loginSuccessful'), 'success');
                    } else {
                        // The user receives a strongly worded warning that his cookie might be stolen.
                        $cookieStolenMapper->deleteCookieStolen($result->getUser()->getId());
                        $this->addMessage($this->getTranslator()->trans('cookieStolen'), 'danger');
                    }

                    if ($rememberMe) {
                        $authTokenModel = new AuthTokenModel();

                        // 9 bytes of random data (base64 encoded to 12 characters) for the selector.
                        // This provides 72 bits of keyspace and therefore 236 bits of collision resistance (birthday attacks)
                        $authTokenModel->setSelector(base64_encode(openssl_random_pseudo_bytes(9)));
                        // 33 bytes (264 bits) of randomness for the actual authenticator. This should be unpredictable in all practical scenarios.
                        $authenticator = openssl_random_pseudo_bytes(33);
                        // SHA256 hash of the authenticator. This mitigates the risk of user impersonation following information leaks.
                        $authTokenModel->setToken(hash('sha256', $authenticator));
                        $authTokenModel->setUserid($result->getUser()->getId());
                        $authTokenModel->setExpires(date('Y-m-d\TH:i:s', strtotime( '+30 days' )));

                        setcookie('remember', $authTokenModel->getSelector().':'.base64_encode($authenticator), strtotime( '+30 days' ), '/', $_SERVER['SERVER_NAME'], false, false);

                        $authTokenMapper = new AuthTokenMapper();
                        $authTokenMapper->addAuthToken($authTokenModel);
                    }
                } else {
                    $this->addMessage($this->getTranslator()->trans($result->getError()), 'warning');
                    $redirectUrl = ['module' => 'user', 'controller' => 'login', 'action' => 'index'];
                }

                $this->redirect($redirectUrl);
            }

            $this->getView()->set('errors', $errors);
        }

        if (!empty($_SESSION['redirect'])) {
            $redirectUrl = $_SESSION['redirect'];
            unset($_SESSION['redirect']);
        }

        $this->getView()->setArray([
            'errors' => $errors,
            'regist_accept' => $this->getConfig()->get('regist_accept'),
            'redirectUrl' => $redirectUrl
        ]);
    }

    public function newpasswordAction()
    {        
        $this->getLayout()->getHmenu()
                ->add($this->getTranslator()->trans('menuUser'), ['controller' => 'index', 'action' => 'index'])
                ->add($this->getTranslator()->trans('newPassword'), ['action' => 'newpassword']);

        if ($this->getRequest()->getPost('saveNewPassword')) {
            $confirmedCode = $this->getRequest()->getParam('code');

            if (empty($confirmedCode)) {
                $this->addMessage('missingConfirmedCode', 'danger');
            } else {
                $userMapper = new UserMapper();
                $user = $userMapper->getUserByConfirmedCode($confirmedCode);

                if(!empty($user)) {
                    $password = trim($this->getRequest()->getPost('password'));
                    $password2 = trim($this->getRequest()->getPost('password2'));

                    if (empty($password)) {
                        $this->addMessage('passwordEmpty', $type = 'danger');
                        $this->redirect(['action' => 'newpassword', 'code' => $confirmedCode]);
                    } elseif (empty($password2)) {
                        $this->addMessage('passwordRetypeEmpty', $type = 'danger');
                        $this->redirect(['action' => 'newpassword', 'code' => $confirmedCode]);
                    } elseif (strlen($password) < 6 OR strlen($password) > 30) {
                        $this->addMessage('passwordLength', $type = 'danger');
                        $this->redirect(['action' => 'newpassword', 'code' => $confirmedCode]);
                    } elseif ($password != $password2) {
                        $this->addMessage('passwordNotEqual', $type = 'danger');
                        $this->redirect(['action' => 'newpassword', 'code' => $confirmedCode]);
                    }

                    if (!empty($password) AND !empty($password2) AND $password == $password2) {
                        $password = (new PasswordService())->hash($password);
                        $user->setConfirmed(1);
                        $user->setConfirmedCode('');
                        $user->setPassword($password);
                        $userMapper->save($user);

                        $this->addMessage('newPasswordSuccess');
                        $this->redirect(['action' => 'index']);
                    }
                } else {
                    $this->addMessage('newPasswordFailed', 'danger');                    
                }
            }
        }
    }

    public function forgotpasswordAction()
    {        
        $this->getLayout()->getHmenu()
                ->add($this->getTranslator()->trans('menuLogin'), ['action' => 'index'])
                ->add($this->getTranslator()->trans('menuForgotPassword'), ['action' => 'forgotpassword']);

        if ($this->getRequest()->getPost('saveNewPassword')) {
            $name = trim($this->getRequest()->getPost('name'));

            if (empty($name)) {
                $this->addMessage('missingNameEmail', 'danger');
            } else {
                $userMapper = new UserMapper();
                $user = $userMapper->getUserByEmail($name);

                if ($user == null) {
                    $user = $userMapper->getUserByName($name);
                }

                if (!empty($user)) {
                    $confirmedCode = md5(uniqid(rand()));
                    $user->setConfirmedCode($confirmedCode);
                    $userMapper->save($user);

                    $name = $user->getName();
                    $email = $user->getEmail();
                    $sitetitle = $this->getConfig()->get('page_title');
                    $confirmCode = '<a href="'.BASE_URL.'/index.php/user/login/newpassword/code/'.$confirmedCode.'" class="btn btn-primary btn-sm">'.$this->getTranslator()->trans('confirmMailButtonText').'</a>';
                    $date = new \Ilch\Date();

                    $layout = '';
                    if (!empty($_SESSION['layout'])) {
                        $layout = $_SESSION['layout'];
                    }

                    if ($layout == $this->getConfig()->get('default_layout') && file_exists(APPLICATION_PATH.'/layouts/'.$this->getConfig()->get('default_layout').'/views/modules/user/layouts/mail/passwordchange.php')) {
                        $messageTemplate = file_get_contents(APPLICATION_PATH.'/layouts/'.$this->getConfig()->get('default_layout').'/views/modules/user/layouts/mail/passwordchange.php');
                    } else {
                        $messageTemplate = file_get_contents(APPLICATION_PATH.'/modules/user/layouts/mail/passwordchange.php');
                    }
                    $messageReplace = [
                            '{content}' => $this->getConfig()->get('password_change_mail'),
                            '{sitetitle}' => $sitetitle,
                            '{date}' => $date->format("l, d. F Y", true),
                            '{name}' => $name,
                            '{confirm}' => $confirmCode,
                            '{footer}' => $this->getTranslator()->trans('noReplyMailFooter')
                    ];
                    $message = str_replace(array_keys($messageReplace), array_values($messageReplace), $messageTemplate);

                    $mail = new \Ilch\Mail();
                    $mail->setTo($email,$name)
                            ->setSubject($this->getTranslator()->trans('automaticEmail'))
                            ->setFrom($this->getTranslator()->trans('automaticEmail'), $sitetitle)
                            ->setMessage($message)
                            ->addGeneralHeader('Content-type', 'text/html; charset="utf-8"');
                    $mail->setAdditionalParameters('-f '.$this->getConfig()->get('standardMail'));
                    $mail->send();

                    $this->addMessage('newPasswordEMailSuccess');
                } else {
                    $this->addMessage('newPasswordFailed', 'danger');
                }
            }
        }
    }
}
