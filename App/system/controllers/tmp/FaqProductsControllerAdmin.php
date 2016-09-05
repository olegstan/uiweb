<?php
namespace app\controllers;

use core\Controller;

class FaqProductsControllerAdmin extends Controller
{
    private $param_url, $options;

    public function set_params($url = null, $options = null)
    {
        $this->param_url = $url;
        $this->options = $options;
    }

    function fetch()
    {
        if (!(isset($_SESSION['admin']) && $_SESSION['admin']=='admin'))
            header("Location: http://".$_SERVER['SERVER_NAME']."/admin/login/");

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        $this->design->assign('edit_module', $this->furl->get_module_by_name('FaqProductControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'faq-products.tpl');
    }
}