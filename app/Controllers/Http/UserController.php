<?php
namespace App\Controllers\Http;

use App\Layers\LayerHttpController;
use Framework\Auth\Auth;
use Framework\Response\Types\HtmlResponse;
use App\Layers\LayerView as View;

class UserController extends LayerHttpController
{
    public function login()
    {
        return HtmlResponse::html(new View('auth/login.php', [
            'title' => 'Вход в личный кабинет Praset.ru',
            'meta_keywords' => 'Вход в личный кабинет Praset.ru',
            'meta_description' => 'Вход в личный кабинет Praset.ru'
        ]));
    }

    public function register()
    {
        return HtmlResponse::html(new View('auth/register.php', [
            'title' => 'Регистрация Praset.ru',
            'meta_keywords' => 'Регистрация Praset.ru',
            'meta_description' => 'Регистрация Praset.ru'
        ]));

//        $this->getCore()->asset->addFooterJS('/libraries/jquery.maskedinput/dist/jquery.maskedinput.min.js');
//
//        $this->getCore()->title = 'Регистрация';
//        $this->getCore()->meta_keywords = 'Регистрация';
//        $this->getCore()->meta_description = 'Регистрация';

//        return HtmlResponse::html(new View('login.php', [
//            'title' => 'Вход в личный кабинет',
//            'meta_keywords' => 'Вход в личный кабинет',
//            'meta_description' => 'Вход в личный кабинет'
//        ]));
        /*$captcha_success = true;
        if ($this->settings->google_recaptcha_is_enabled)
        {
            $g_recaptcha_response = $this->request->post('g-recaptcha-response');

            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = array(
                'secret' => $this->settings->google_recaptcha_secret_key,
                'response' => $g_recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']);

            // use key 'http' even if you send the request to https://...
            $options = array(
                'http' => array(
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                ),
            );
            $context  = stream_context_create($options);
            $result = file_get_contents($url, false, $context);

            $result = json_decode($result);

            if (!$result->success)
            {
                $captcha_success = false;
                $this->design->assign('message_error', 'Неверная капча');
                $this->design->assign('user_register', $user_register);

            }
        }*/




//        return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path . '/html/register.tpl'));
    }

    public function logout()
    {
        Auth::logout();

//        $this->getCore()->auth = new Auth();
//        $this->getCore()->flash = new Flash();
//        $this->getCore()->flash->set('auth', 'Вы успешно вышли из своего аккаунта');
        $this->redirect->auth();
    }

    public function newPassword()
    {
        $this->getCore()->title = 'Смена пароля';
        $this->getCore()->meta_keywords = 'Смена пароля';
        $this->getCore()->meta_description = 'Смена пароля';

        return Response::html($this->render(TPL . '/' . $this->getCore()->tpl_path . '/html/new-password.tpl'));
    }

    public function forgotPassword()
    {
        /*$error_message = "";
        $success_message = "";
        $email = $this->request->post('email');
        $user = $this->users->get_user($email);
        if (!$user)
            $error_message = "user_not_found";
        else
        {
            $reset_url = md5($this->salt . $user->password . md5($user->password) . $user->id);
            $datetime = new DateTime();
            $datetime->modify('+1 day');
            $reset_date = $datetime->format('Y-m-d G:i');

            $this->users->update_user($user->id, array('reset_url'=> $reset_url, 'reset_date'=> $reset_date));
            $this->notify_email->email_reset_password($user->id);
            $success_message = "mail_sended";
        }
        if (!empty($error_message))
            $this->design->assign('error_message', $error_message);
        if (!empty($success_message))
            $this->design->assign('success_message', $success_message);*/
    }
}