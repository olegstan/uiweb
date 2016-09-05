<?php
namespace app\controllers;

use core\Controller;

class SeoControllerAdmin extends Controller
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

        if ($this->request->method('post'))
        {
            $this->settings->prefix_product = $this->request->post('prefix_product');
            $this->settings->postfix_product = $this->request->post('postfix_product');
            $this->settings->prefix_category_url = $this->request->post('prefix_category_url');
            $this->settings->postfix_category_url = $this->request->post('postfix_category_url');
            $this->settings->prefix_product_url = $this->request->post('prefix_product_url');
            $this->settings->postfix_product_url = $this->request->post('postfix_product_url');
            $this->settings->prefix_brand_url = $this->request->post('prefix_brand_url');
            $this->settings->postfix_brand_url = $this->request->post('postfix_brand_url');
            $this->design->assign('message_success', 'saved');
        }

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        $admin_modules = $this->furl->get_modules(array('branch'=>'admin'));
        $frontend_modules = $this->furl->get_modules(array('branch'=>'frontend'));
        $this->design->assign('admin_modules', $admin_modules);
        $this->design->assign('frontend_modules', $frontend_modules);

        return $this->design->fetch($this->design->getTemplateDir('admin').'seo.tpl');
    }
}