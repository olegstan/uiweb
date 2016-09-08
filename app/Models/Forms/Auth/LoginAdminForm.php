<?php
namespace app\models\user;

use app\layer\LayerModel;

use core\helper\Hash;
use core\helper\Response;

class LoginAdminForm extends LayerModel
{
    protected $table = 'mc_access_users';

    public $value;
    public $field;
    /**
     * @param string $scenario
     * @return array
     */

    public function rules($scenario)
    {
        switch($scenario){
            case 'validate':
                return [
                    ['field' => 'value', 'filter' => 'trim']
                ];
                break;
            default:
                return [
                    ['field' => 'username', 'filter' => 'trim'],
                    ['field' => 'password', 'filter' => 'trim'],
                ];
                break;
        }
    }

    public function validateRules($scenario)
    {
        switch($scenario){
            case 'detect_email':
                return [
                    ['field' => 'username', 'property' => 'username', 'rule' => 'email', 'msg' => ''],
                ];
                break;
            case 'login':
                return [
                    ['field' => 'username', 'property' => 'username', 'rule' => 'empty', 'msg' => 'Пожалуйста введите логин'],
                    ['field' => 'password', 'property' => 'password', 'rule' => 'empty', 'msg' => 'Пожалуйста введите пароль'],
                ];
                break;
            /**
             * валидация
             */
            case 'username':
                return [
                    ['field' => 'username', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Пожалуйста введите логин'],
                ];
                break;
            case 'password':
                return [
                    ['field' => 'password', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Пожалуйста введите пароль'],
                ];
                break;
        }
    }

    public function validateForm()
    {
        $this->load($_POST, 'validate');
        $field = $this->getCore()->request->post('field');

        if (count($this->validate($field))) {
            return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
        }else{
            return Response::json(['result' => 'success', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
        }
    }

    public function login()
    {
        $password = $this->getCore()->request->post('password');

        $password_hash = Hash::password($password);
        /**
         * отправляется поле под name="email"
         */
        $email_or_phone = $this->getCore()->request->post('username');


        /**
         * сначала проверим переданные данные на правильность
         */

        $this->load($_POST);

        $error_message = 'Логин или пароль введены неверно.';
        if (count($this->validate('login'))) {
            return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
        }else {
            $success_array = $this->validation->a_success;

            /**
             * определяем что передано email или телефон
             * по умолчанию проверим на соответствие email
             * если не соответствует будем считать что это телефон
             */

            if (count($this->validate('detect_email'))) {
                //сработает если phone
                $user = (new User)
                    ->query()
                    ->select()
                    ->where('phone = :phone AND password = :password', [':phone' => $email_or_phone, ':password' => $password_hash])
                    ->limit()
                    ->execute()
                    ->one()
                    ->getResult();
            } else {
                //сработает если email
                $user = (new User)
                    ->query()
                    ->select()
                    ->where('email = :email AND password = :password', [':email' => $email_or_phone, ':password' => $password_hash])
                    ->limit()
                    ->execute()
                    ->one()
                    ->getResult();
            }
            unset($this->validation->a_errors['username']);

            /**
             * проверка не заблокирован ли пользователь
             */

            if($user){
                if(!$user->is_enabled){
                    return Response::json(['result' => 'error', 'error' => 'Учетная запись отключена.', 'success' => $success_array, 'errors' => $this->validation->a_errors]);
                }
            }

            if($user){
                $this->getCore()->auth->login($user);
                $this->getCore()->flash->set('auth', 'Вы успешно авторизовались');
                return Response::json(['result' => 'success', 'success' => 'Вы успешно авторизовались']);
            }else{
                return Response::json(['result' => 'error', 'error' => $error_message, 'success' => $success_array, 'errors' => $this->validation->a_errors]);
            }
        }
    }
}