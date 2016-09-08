<?php
namespace app\controllers;

use core\Controller;

class CurrencyControllerAdmin extends Controller
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
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        $main_module =  $this->furl->get_module_by_name('CurrenciesControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post'))
        {
            $currency = new stdClass();
            $currency->id = $this->request->post('id', 'integer');
            $currency->name = $this->request->post('name', 'string');
            $currency->sign = $this->request->post('sign');
            $currency->sign_simple = $this->request->post('sign_simple');
            $currency->code = $this->request->post('code', 'string');
            $currency->rate_from = $this->request->post('rate_from')? $this->request->post('rate_from') : 1;
            $currency->rate_to = $this->request->post('rate_to') ? $this->request->post('rate_to') : 1;
            $currency->cents = $this->request->post('cents', 'integer');
            $currency->is_enabled = $this->request->post('is_enabled', 'boolean');
            $currency->use_main = $this->request->post('use_main', 'boolean');
            $currency->use_admin = $this->request->post('use_admin', 'boolean');
            $currency->auto_update = $this->request->post('auto_update', 'boolean');

            $close_after_save = $this->request->post('close_after_save', 'integer');

            if(empty($currency->id))
            {
                $currency->id = $this->currencies->add_currency($currency);
                $this->design->assign('message_success', 'added');
            }
            else
            {
                $old_currency = $this->currencies->get_currency($currency->id);
                $this->currencies->update_currency($currency->id, $currency);

                if ($old_currency->use_main != $currency->use_main ||
                    $old_currency->use_admin != $currency->use_admin ||
                    $old_currency->rate_to != $currency->rate_to)
                        $this->tags->recreate_auto_properties();

                $this->design->assign('message_success', 'updated');
            }
            $currency = $this->currencies->get_currency(intval($currency->id));

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url);
        }
        else
        {
            $id = 0;
            $mode = "";
            $response['success'] = false;
            $json_answer = false;
            foreach($this->params_arr as $p=>$v)
            {
                switch ($p)
                {
                    case "id":
                        if (is_numeric($v))
                            $id = intval($v);
                        break;
                    case "mode":
                        $mode = strval($v);
                        break;
                    case "ajax":
                        $json_answer = true;
                        unset($this->params_arr[$p]);
                        break;
                }
            }

            if (!empty($id))
                $currency = $this->currencies->get_currency($id);

            if (!empty($mode) && !empty($currency))
                switch($mode){
                    case "delete":
                        $this->currencies->delete_currency($id);
                        $response['success'] = true;
                        break;
                    case "light":
                        $this->currencies->update_currency($id, array('is_enabled'=>1-$currency->is_enabled));
                        $response['success'] = true;
                        break;
                    case "favorite":
                        $this->currencies->update_currency($id, array('use_admin'=>1-$currency->use_admin));
                        $this->tags->recreate_auto_properties();
                        $response['success'] = true;
                        break;
                    case "flag":
                        $this->currencies->update_currency($id, array('use_main'=>1-$currency->use_main));
                        $this->tags->recreate_auto_properties();
                        $response['success'] = true;
                        break;
                }
            else
                if ($mode == "update_rate")
                {
                    $iso = $this->params_arr['iso'];
                    $response['success'] = true;
                    $response['data'] = $this->currencies->update_currency_rate($iso);
                }

            if ($json_answer)
            {
                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode($response);
                die();
            }
        }
        if (isset($currency))
            $this->design->assign('currency_item', $currency);
        return $this->design->fetch($this->design->getTemplateDir('admin').'currency.tpl');
    }
}