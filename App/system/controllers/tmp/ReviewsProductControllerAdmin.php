<?php
namespace app\controllers;

use core\Controller;

class ReviewsProductControllerAdmin extends Controller
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

        $edit_module = $this->furl->get_module_by_name('ReviewsProductControllerAdmin');
        $main_module =  $this->furl->get_module_by_name('ReviewsProductsControllerAdmin');
        $this->design->assign('main_module', $main_module);

        if ($this->request->method('post') && !isset($_FILES['uploaded-images']) &&
            (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
        {
            $review = new stdClass();
            $review->id = $this->request->post('id', 'integer');
            $review->is_visible = $this->request->post('is_visible', 'boolean');
            $review->rating = $this->request->post('rating');
            $review->short = $this->request->post('short');
            $review->pluses = $this->request->post('pluses');
            $review->minuses = $this->request->post('minuses');
            $review->comments = $this->request->post('comments');

            $review->recommended = $this->request->post('recommended', 'boolean');

            $review_date = $this->request->post('datetime_date');
            $review_time = $this->request->post('datetime_time');
            $datetime = DateTime::createFromFormat('d.m.Y G:i', $review_date." ".$review_time);
            $review->datetime = $datetime->format('Y-m-d G:i');

            $review->helpful = $this->request->post('helpful');
            $review->nothelpful = $this->request->post('nothelpful');

            $review_from = $this->request->post('review_from');
            if ($review_from == "user"){
                $user_id = $this->request->post('user_id');
                if ($user_id){
                    $review->user_id = $user_id;
                    $u = $this->users->get_user($user_id);
                    $review->name = $u->name;
                }
            }
            else{
                $name = $this->request->post('name');
                if ($name){
                    $review->name = $name;
                    $review->user_id = NULL;
                }
            }

            $close_after_save = $this->request->post('close_after_save', 'integer');

            $this->reviews->update_review($review->id, $review);
            $this->design->assign('message_success', 'updated');

            $this->reviews->update_review_rank($review->id);

            $review = $this->reviews->get_review(intval($review->id));

            $return_page = $this->request->post('return_page');

            if ($close_after_save && $main_module)
                header("Location: ".$this->config->root_url.$main_module->url.($return_page>1?'?page='.$return_page:''));
        }
        else
            if ($this->request->method('post') && isset($_FILES['uploaded-images']) &&
                (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'))
            {
                $uploaded = $this->request->files('uploaded-images');
                $object_id = $this->request->post('object_id');

                foreach($uploaded as $index=>$ufile)
                    $img = $this->image->add_image('reviews', $object_id, 'review', $ufile['name'], $ufile['tmp_name']);

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
                $multiple = false;
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
                        case "page":
                            $this->design->assign('page', intval($v));
                            break;
                        case "sort":
                            $this->design->assign('sort', $v);
                            break;
                        case "sort_type":
                            $this->design->assign('sort_type', $v);
                            break;
                    }
                }

                if (!empty($id)){
                    $review = $this->reviews->get_review($id);
                    if (!$review->moderated){
                        $review->moderated = 1;
                        $this->reviews->update_review($id, array('moderated'=>1));
                    }
                }

                if (!empty($mode) && ((isset($review) && !empty($review)) || $multiple || $mode=="delete_claim"))
                    switch($mode){
                        case "delete":
                            $this->reviews->delete_review($id);
                            $response['success'] = true;
                            break;
                        case "delete_claim":
                            $claim_id = intval($this->params_arr['claim_id']);
                            $this->reviews->delete_claim($claim_id);
                            $response['success'] = true;
                            break;
                        case "toggle":
                            $this->reviews->update_review($id, array('is_visible'=>1 - $review->is_visible));
                            $response['success'] = true;
                            $response['is_visible'] = 1 - $review->is_visible;
                            break;
                        case "get_images":
                            $this->design->assign('object', $review);
                            $images = $this->image->get_images('reviews', $id);
                            $this->design->assign('images', $images);
                            $this->design->assign('images_object_name', 'reviews');
                            $response['success'] = true;
                            $response['data'] = $this->design->fetch($this->design->getTemplateDir('admin').'object-images.tpl');
                            break;
                        case "delete_image":
                            $image_id = intval($this->params_arr['image_id']);
                            $this->image->delete_image('reviews', $id, $image_id);
                            $response['success'] = true;
                            break;
                        case "upload_internet_image":
                            $image_url = base64_decode($this->params_arr['image_url']);
                            $this->image->add_internet_image('reviews', $id, 'review', $image_url);
                            $response['success'] = true;
                            break;
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

        if (!empty($review))
        {
            if (!empty($review->user_id))
                $review->user = $this->users->get_user($review->user_id);

            $review->claims = $this->reviews->get_claims(array('review_id' => $review->id));

            $review->product = $this->products->get_product($review->product_id);

            $review->product_variants = $this->variants->get_variants(array('product_id'=>$review->product->id, 'is_visible'=>1));
            $review->product_variant = @reset($review->product_variants);

            $review->product->in_stock = false;
            $review->product->in_order = false;
            foreach($review->product_variants as $rv)
                if ($rv->stock > 0)
                    $review->product->in_stock = true;
                else
                    if ($rv->stock < 0)
                        $review->product->in_order = true;

            $review->product_rating = $this->reviews->calc_product_rating($review->product_id);
            $review->product_images = $this->image->get_images('products', $review->product_id);
            $review->product_image = @reset($review->product_images);

            // Изображения товара
            $images = $this->image->get_images('reviews', $review->id);
            $this->design->assign('images', $images);

            $rank_arr = $this->reviews->calc_review_rank($review->id);
            $this->design->assign('rank_arr', $rank_arr);

            if ($review->rank != ($rank_arr['newest_rank'] + $rank_arr['popular_rank'] + $rank_arr['fill_rank'] - $rank_arr['claim_rank'])){
                $review->rank = $rank_arr['newest_rank'] + $rank_arr['popular_rank'] + $rank_arr['fill_rank'] - $rank_arr['claim_rank'];
                $this->reviews->update_review_rank($review->id);
            }

            $this->design->assign('review', $review);
        }
        else
            header("Location: " . $config->root_url . $main_module->url);

        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));
        $this->design->assign('all_users', $this->users->get_users());
        return $this->design->fetch($this->design->getTemplateDir('admin').'reviews-product.tpl');
    }
}