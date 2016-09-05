<?php
namespace Framework\Auth\Form;

use app\layer\LayerModel;
use core\helper\Hash;
use core\helper\Response;
use Framework\Model\Types\DatabaseModel;

class NewPasswordForm extends DatabaseModel
{
    protected $table = 'mc_access_users';

    public function rules($scenario)
    {
        return [
            ['field' => 'old_password', 'filter' => 'trim'],
            ['field' => 'new_password', 'filter' => 'trim'],
        ];
    }

    public function validateRules($scenario)
    {
        return [
            ['field' => 'old_password', 'property' => 'old_password', 'rule' => 'empty', 'msg' => 'Пожалуйста введите старый пароль'],
            ['field' => 'new_password', 'property' => 'new_password', 'rule' => 'empty', 'msg' => 'Пожалуйста введите новый пароль'],
        ];
    }

    public function validateForm()
    {

    }

    public function change()
    {
        $this->load($_POST, 'validate');

        if(count($this->validate())){
            return Response::json(['result' => 'error', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
        }else{
            $current_password = $this->getCore()->auth->user->password;

            $old_password = $this->getCore()->request->post('old_password');

            if(Hash::verify($old_password, $current_password)){

            }

            $password = $this->getCore()->request->post('password');

            $password_hash = Hash::password($password);



            return Response::json(['result' => 'success', 'error' => 'Поля не заполены', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors]);
        }





    }
}