<?php
namespace app\controllers;

use core\Controller;

class MainControllerAdmin extends Controller
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

#######################################
#### ORDERS
#######################################
        $new_status_order = $this->orders->get_status('Новые');
        $last_orders = $this->orders->get_orders(array('limit'=>5, 'status_type'=>'processed', 'moderated'=>0));

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
        foreach($last_orders as $index=>$order)
        {
            $last_orders[$index]->purchases = $this->orders->get_purchases(array('order_id'=>$order->id));
            $last_orders[$index]->user = $this->users->get_user(intval($order->user_id));

            $last_orders[$index]->total_price = 0;
            foreach($last_orders[$index]->purchases as $purchase)
                $last_orders[$index]->total_price += $purchase->amount * $purchase->price;
            if ($last_orders[$index]->discount_type == 0)
                $last_orders[$index]->total_price = $last_orders[$index]->total_price - $last_orders[$index]->total_price * $last_orders[$index]->discount / 100;
            else
                $last_orders[$index]->total_price = $last_orders[$index]->total_price - $last_orders[$index]->discount;
            if (!$last_orders[$index]->separate_delivery)
                $last_orders[$index]->total_price += $last_orders[$index]->delivery_price;

            if (date("Ymd", $last_orders[$index]->date) == $todayday->format("Ymd"))
                $last_orders[$index]->day_str = "Сегодня";
            else
                if (date("Ymd", $last_orders[$index]->date) == $yesterday->format("Ymd"))
                    $last_orders[$index]->day_str = "Вчера";
                else
                    if (date("Y", $last_orders[$index]->date) == date("Y"))
                        $last_orders[$index]->day_str = $days_arr[date("l",$last_orders[$index]->date)] . " " . date("d.m", $last_orders[$index]->date);
                    else
                        $last_orders[$index]->day_str = $days_arr[date("l",$last_orders[$index]->date)] . " " . date("d.m.Y", $last_orders[$index]->date);
        }

#############################
#### REVIEWS
#############################
        $reviews = $this->reviews->get_reviews(array('moderated'=>0, 'limit'=>5));
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

######################################
#### CALLBACKS
######################################
        $callbacks = $this->callbacks->get_callbacks(array('moderated'=>0, 'limit'=>5));
        foreach($callbacks as $index=>$callback)
        {
            if ($callback->user_id > 0)
                $callbacks[$index]->user = $this->users->get_user($callback->user_id);

            if (date("Ymd", $callbacks[$index]->created) == $todayday->format("Ymd"))
                $callbacks[$index]->day_str = "Сегодня";
            else
                if (date("Ymd", $callbacks[$index]->created) == $yesterday->format("Ymd"))
                    $callbacks[$index]->day_str = "Вчера";
                else
                    $callbacks[$index]->day_str = $days_arr[date("l",$callbacks[$index]->created)];
        }

        $this->design->assign('last_orders', $last_orders);
        $this->design->assign('last_reviews', $reviews);
        $this->design->assign('last_callbacks', $callbacks);
        $this->design->assign('order_module', $this->furl->get_module_by_name('OrderProcessedControllerAdmin'));
        $this->design->assign('orders_module', $this->furl->get_module_by_name('OrdersProcessedControllerAdmin'));
        $this->design->assign('callbacks_module', $this->furl->get_module_by_name('ContactsOrdersCallsControllerAdmin'));
        $this->design->assign('reviews_module', $this->furl->get_module_by_name('ReviewsProductsControllerAdmin'));
        $this->design->assign('review_module', $this->furl->get_module_by_name('ReviewsProductControllerAdmin'));

        if($this->page)
        {
            $this->design->assign('meta_title', $this->page->meta_title);
            $this->design->assign('meta_keywords', $this->page->meta_keywords);
            $this->design->assign('meta_description', $this->page->meta_description);
        }

        return $this->design->fetch($this->design->getTemplateDir('admin').'main.tpl');
    }
}