<?php
namespace app\controllers\ajax;

use app\layer\LayerController;
use app\models\user\LoginForm;
use app\models\user\NewPasswordForm;
use app\models\user\RegisterForm;

class UserController extends LayerController
{
    public function validateLogin()
    {
        return (new LoginForm())->validateForm();
    }

    public function login()
    {
        return (new LoginForm())->login();
    }

    public function validateRegister()
    {
        return (new RegisterForm())->validateForm();
    }

    public function register()
    {
        return (new RegisterForm())->register();
    }

    public function validateNewPassword()
    {
        return (new NewPasswordForm())->validateForm();
    }

    public function newPassword()
    {
        return (new NewPasswordForm())->change();
    }

    public function logout()
    {
        $this->getCore()->auth->logout();
    }
}