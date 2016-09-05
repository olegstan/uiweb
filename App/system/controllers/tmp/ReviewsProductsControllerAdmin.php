<?php
namespace app\controllers;

use core\Controller;

class ReviewsProductsControllerAdmin extends Controller
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

        $filter = array();
        $current_page = 1;

        foreach($this->params_arr as $p=>$v)
        {
            switch ($p)
            {
                case "keyword":
                    if (!empty($this->params_arr[$p]))
                    {
                        $filter[$p] = $v;
                        $this->design->assign('keyword', $v);
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "page":
                    if (!empty($this->params_arr[$p]))
                        $current_page = intval($v);
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort":
                    if (!empty($this->params_arr[$p]))
                        $filter[$p] = $v;
                    else
                        unset($this->params_arr[$p]);
                    break;
                case "sort_type":
                    if (!empty($this->params_arr[$p]))
                    {
                        if (!array_key_exists('sort_type', $filter))
                            $filter[$p] = $v;
                    }
                    else
                        unset($this->params_arr[$p]);
                    break;
            }
        }

        /*Зададим сортировку по умолчанию*/
        if (!array_key_exists('sort', $this->params_arr))
            $filter['sort'] = 'date';

        if (!array_key_exists('sort_type', $this->params_arr))
            $filter['sort_type'] = 'desc';

        $this->design->assign('current_params', $this->params_arr);

        $reviews_count = $this->reviews->count_reviews($filter);

        // Постраничная навигация
        if (array_key_exists('page', $this->params_arr) && $this->params_arr['page'] == 'all')
            $items_per_page = $reviews_count;
        else
            $items_per_page = 10;

        $this->design->assign('reviews_count', $reviews_count);
        $pages_num = $items_per_page>0 ? ceil($reviews_count/$items_per_page): 0;
        $this->design->assign('total_pages_num', $pages_num);

        // Если страница не задана, то равна 1
        $current_page = max(1, $current_page);
        $current_page = min($current_page, $pages_num);
        $this->design->assign('current_page_num', $current_page);

        $filter['page'] = $current_page;
        $filter['limit'] = $items_per_page;

        $todayday = new DateTime();
        $yesterday = new DateTime();
        $yesterday->modify('-1 day');
        $days_arr = array('Monday' => 'Пн',
                            'Tuesday' => 'Вт',
                            'Wednesday' => 'Ср',
                            'Thursday' => 'Чт',
                            'Friday' => 'Пт',
                            'Saturday' => 'Сб',
                            'Sunday' => 'Вс');

        $reviews = $this->reviews->get_reviews($filter);
        foreach($reviews as $index=>$review){
            if (!empty($review->user_id)){
                $reviews[$index]->user = $this->users->get_user($review->user_id);
                $reviews[$index]->review_count = $this->reviews->count_reviews(array('user_id' => $review->user_id));
            }
            else
                $reviews[$index]->review_count = $this->reviews->count_reviews(array('session_id' => $review->session_id));
            $reviews[$index]->claims_count = $this->reviews->count_claims(array('review_id' => $review->id));

            $reviews[$index]->product = $this->products->get_product($review->product_id);
            $reviews[$index]->product_rating = $this->reviews->calc_product_rating($review->product_id);
            $reviews[$index]->product_images = $this->image->get_images('products', $review->product_id);
            $reviews[$index]->product_image = @reset($reviews[$index]->product_images);

            if (date("Ymd", $reviews[$index]->datetime) == $todayday->format("Ymd"))
                $reviews[$index]->day_str = "Сегодня";
            else
                if (date("Ymd", $reviews[$index]->datetime) == $yesterday->format("Ymd"))
                    $reviews[$index]->day_str = "Вчера";
                else
                    $reviews[$index]->day_str = $days_arr[date("l",$reviews[$index]->datetime)];

            $reviews[$index]->images = $this->image->get_images('reviews', $review->id);
        }

        $this->design->assign('reviews', $reviews);
        $this->design->assign('params_arr', $this->params_arr);

        $this->design->assign('edit_module', $this->furl->get_module_by_name('ReviewsProductControllerAdmin'));
        $this->design->assign('product_module', $this->furl->get_module_by_name('ProductControllerAdmin'));
        return $this->design->fetch($this->design->getTemplateDir('admin').'reviews-products.tpl');
    }
}