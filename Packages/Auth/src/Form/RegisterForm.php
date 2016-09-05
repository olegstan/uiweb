<?php
namespace Framework\Auth\Form;

use app\layer\LayerModel;

use core\helper\Hash;
use core\helper\Response;
use Framework\Model\Types\DatabaseModel;

class RegisterForm extends DatabaseModel
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
                    ['field' => 'name', 'filter' => 'trim'],
                    ['field' => 'phone', 'filter' => 'trim'],
                    ['field' => 'email', 'filter' => 'trim'],
                    ['field' => 'password', 'filter' => 'trim']
                ];
                break;
        }
    }

    public function validateRules($scenario)
    {
        switch($scenario){
            case 'register':
                return [
                    ['field' => 'email', 'property' => 'email', 'rule' => 'unique', 'class_name' => '\\app\\models\\user\\User', 'class_field' => 'email', 'msg' => 'Пользователь с таким e-mail уже зарегистрирован'],
                    ['field' => 'email', 'property' => 'email', 'rule' => 'email', 'msg' => 'Адрес электронной почты не соответствует формату'],
                    ['field' => 'email', 'property' => 'email', 'rule' => 'max_length', 'length' => 253, 'msg' => 'Адрес электронной почты не может содержать больше 320 символов'],
                    ['field' => 'email', 'property' => 'email', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                    ['field' => 'name', 'property' => 'name', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                    ['field' => 'phone', 'property' => 'phone', 'rule' => 'pattern', 'pattern' => '\\+7 \\([0-9]{3}\\) [0-9]{3}\\-[0-9]{2}\\-[0-9]{2}', 'msg' => 'Телефон не соответствует формату'],
                    ['field' => 'phone', 'property' => 'phone', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                    ['field' => 'password', 'property' => 'password', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                ];
                break;
            /**
             * валидация
             */
            case 'name':
                return [
                    ['field' => 'name', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                ];
                break;
            case 'email':
                return [
                    ['field' => 'email', 'property' => 'value', 'rule' => 'unique', 'class_name' => '\\app\\models\\user\\User', 'class_field' => 'email', 'msg' => 'Пользователь с таким e-mail уже зарегистрирован'],
                    ['field' => 'email', 'property' => 'value', 'rule' => 'email', 'msg' => 'Адрес электронной почты не соответствует формату'],
                    ['field' => 'email', 'property' => 'value', 'rule' => 'max_length', 'length' => 253, 'msg' => 'Адрес электронной почты не может содержать больше 320 символов'],
                    ['field' => 'email', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                ];
                break;
            case 'phone':
                return [
                    ['field' => 'phone', 'property' => 'value', 'rule' => 'pattern', 'pattern' => '\\+7 \\([0-9]{3}\\) [0-9]{3}\\-[0-9]{2}\\-[0-9]{2}', 'msg' => 'Телефон не соответствует формату'],
                    ['field' => 'phone', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                ];
                break;
            case 'password':
                return [
                    ['field' => 'password', 'property' => 'value', 'rule' => 'empty', 'msg' => 'Это поле обязательно для заполнения'],
                ];
                break;
        }
    }

    public function validateForm()
    {
        $this->load($_POST, 'validate');

        $field = $this->getCore()->request->post('field');

        if($field == 'phone'){
            $phone = $this->getCore()->request->post('value');
            $symbols = ['+', '7', '(', ')', '-'];
            $phone = str_replace($symbols, '', $phone);
            $user = (new User)
                ->query()
                ->select()
                ->where('phone = :phone', [':phone' => $phone])
                ->limit()
                ->execute()
                ->one()
                ->getResult();
        }


        if(count($this->validate($field))){
            if($user){
                $this->validation->a_errors['phone'] = 'Пользователь с таким телефоном уже зарегистрирован';
                return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
            }else{
                return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
            }
        }else{
            return Response::json(['result' => 'success', 'success' => $this->validation->a_success]);
        }
    }

    public function register()
    {
        /**
         * сначала проверим переданные данные на правильность
         */

        $this->load($_POST);

        $phone = $this->getCore()->request->post('phone');
        $symbols = ['+', '7', '(', ')', '-'];
        $phone = str_replace($symbols, '', $phone);
        $user = (new User)
            ->query()
            ->select()
            ->where('phone = :phone', [':phone' => $phone])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        $error_message = 'Имя пользователя или пароль введены неверно.';
        if (count($this->validate('register')) || $user) {
            if($user){
                $this->validation->a_errors['phone'] = 'Пользователь с таким телефоном уже зарегистрирован';
                return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
            }else{
                return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
            }
        }else{
            $user = new User();
            $user->phone = $phone;

            $password = $this->getCore()->request->post('password');
            $password_hash = Hash::password($password);

            $user->name = $this->getCore()->request->post('name');

            /**
             * удалим из телефона символы
             * + 7 ( ) -
             */

            $user->email = $this->getCore()->request->post('email');
            $user->password = $password_hash;
            $user->group_id = 4;
            $user->is_enabled = 1;
            $user->created_dt = date('Y-m-d H:i:s');
            $user->insert();

            $this->getCore()->auth->login($user);
            $this->getCore()->flash->set('auth', 'Поздравляем! Теперь, используя личный кабинет, вы можете сменить личные данные или пароль. Также Вам доступна история заказов и управление оповещениями на почту и SMS.');
            return Response::json(['result' => 'success', 'success' => $this->validation->a_success]);
        }
    }
}