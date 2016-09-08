<?php
namespace app\controllers;

use core\Controller;

class SlideshowControllerAdmin extends Controller
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

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']))
        {
            $this->settings->slideshow_is_enabled = $this->request->post('slideshow_is_enabled', 'boolean');
            $this->settings->slideshow_animation_speed = $this->request->post('slideshow_animation_speed', 'integer');
            $this->settings->slideshow_change_speed = $this->request->post('slideshow_change_speed', 'integer');

            $positions = (array)$this->request->post('position');
            foreach($positions as $pos=>$slide_id)
                $this->slideshow->update_slide($slide_id, array('position' => $pos));

            $urls = (array)$this->request->post('url');
            foreach($urls as $slide_id=>$url)
                $this->slideshow->update_slide($slide_id, array('url' => $url));

            $this->design->assign('message_success', 'saved');
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                $images = $this->image->get_images('slideshow', $object_id);
                foreach($images as $i)
                    $this->image->delete_image('slideshow', $object_id, $i->id);

                foreach($uploaded as $index=>$ufile)
                    $img = $this->image->add_image('slideshow', $object_id, 'slideshow', $ufile['name'], $ufile['tmp_name']);

                header("Content-type: application/json; charset=UTF-8");
                header("Cache-Control: must-revalidate");
                header("Pragma: no-cache");
                header("Expires: -1");
                print json_encode(1);
                die();
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
                    $slide = $this->slideshow->get_slide($id);

                if (!empty($mode) && !empty($slide))
                    switch($mode){
                        case "delete":
                            $this->slideshow->delete_slide($id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->slideshow->update_slide($id, array('is_visible'=>1-$slide->is_visible));
                            $response['success'] = true;
                            break;
                        case "get_images":
                            $this->design->assign('object', $slide);
                            $images = $this->image->get_images('slideshow', $id);
                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'slideshow');
                            $response['success'] = true;
                            $response['data'] = array();
                            foreach($images as $i)
                                $response['data'][] = $this->design->resize_modifier($i->filename, 'slideshow');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);
                            $this->image->delete_image('slideshow', $id, $image_id);
                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);
                            $this->image->add_internet_image('slideshow', $id, 'slideshow', $image_url);
                            $response['success'] = true;
                            break;
                    }
                elseif (!empty($mode))
                {
                    switch($mode){
                        case "add_slide":
                            $slide = new StdClass;
                            $slide->url = "";
                            $slide->is_visible = 1;
                            $slide_id = $this->slideshow->add_slide($slide);
                            $response['success'] = $slide_id !== FALSE;
                            $response['data'] = $slide_id;
                            break;
                    }
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

        $slides = $this->slideshow->get_slides();
        foreach($slides as $index=>$slide)
        {
            $images = $this->image->get_images('slideshow', $slide->id);
            if (!empty($images))
                $slides[$index]->image = @reset($images);
        }
        $this->design->assign('slides', $slides);

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'slideshow.tpl');
    }
}