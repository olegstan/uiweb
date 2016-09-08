<?php
namespace app\controllers;

use core\Controller;

class LoginControllerAdmin extends Controller
{
    private $param_url, $params_arr, $options;

    public function set_params($url = null, $options = null)
    {
        $this->options = $options;

        $url = urldecode(trim($url, '/'));
        $delim_pos = mb_strpos($url, '?', 0, 'utf-8');

        if ($delim_pos === false)
        {
            $this->param_url = $url;
            $this->params_arr = array();
        }
        else
        {
            $this->param_url = trim(mb_substr($url, 0, $delim_pos, 'utf-8'), '/');
            $url = mb_substr($url, $delim_pos+1, mb_strlen($url, 'utf-8')-($delim_pos+1), 'utf-8');
            $this->params_arr = array();
            foreach(explode("&", $url) as $p)
            {
                $x = explode("=", $p);
                $this->params_arr[$x[0]] = "";
                if (count($x)>1)
                    $this->params_arr[$x[0]] = $x[1];
            }
        }
    }

    function fetch()
    {
        if (isset($this->options["logout"]))
        {
            //deprecated in PHP 5.4
            //session_unregister('admin');
            //session_unregister('user_id');
            unset($_SESSION['admin']);
            unset($_SESSION['user_id']);
            header('Location: http://'.$_SERVER['SERVER_NAME']."/");
        }

        $return_url = "";
        if (array_key_exists('return_url', $this->params_arr))
            $return_url = base64_decode($this->params_arr['return_url']);

        if ($this->request->method('post') && $this->request->post('username'))
        {
            $error_message = "";
            $username = $this->request->post('username');
            $password = $this->request->post('password');

            if($user_id = $this->users->check_password($username, $password))
            {
                $user = $this->users->get_user($user_id);
                $granted = $this->users->check_permission($user_id, "LoginControllerAdmin", "admin");
                if($user->is_enabled && $granted)
                {
                    @ini_set('session.gc_maxlifetime', $this->settings->session_lifetime);    // 86400 = 24 часа
                    @ini_set('session.cookie_lifetime', 0);                 // 0 - пока браузер не закрыт
                    session_start();
                    $_SESSION['id'] = session_id();
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['admin'] = "admin";
                    if (empty($return_url))
                        header('Location: http://'.$_SERVER['SERVER_NAME']."/admin/");
                    else
                        header('Location: ' . $return_url);
                }
                else
                    if (!$user->is_enabled)
                        $error_message = "account_disabled";
                    else
                        $error_message = "access_denied";
            }
            else
                $error_message = "auth_error";

            if (!empty($error_message))
            {
                $this->design->assign('username', $username);
                $this->design->assign('error_message', $error_message);
            }
        }

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'login.tpl');
    }
}