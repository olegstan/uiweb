<?php
namespace app\system\controllers;

use app\layer\LayerAdminController;
use app\models\Callback;
use app\models\Order;
use app\models\order\OrderStatus;
use app\models\Review;
use core\helper\Response;
use \DateTime;

class IndexController extends LayerAdminController
{
    public function index()
    {
        $today = new DateTime();
        $yesterday = (new DateTime())->modify('-1 day');

        $order_status = (new OrderStatus())
            ->query()
            ->with(['orders'])
            ->select()
            ->where('name = :name', [':name' => 'Новые'])
            ->limit()
            ->execute()
            ->one()
            ->getResult();

        foreach($order_status->orders as $order){
            $order->getDayString($today, $yesterday);
        }

        $this->design->assign('order_status', $order_status);

        $reviews = (new Review())
            ->query()
            ->with(['users', 'products', 'images'])
            ->select()
            ->where('moderated = 0')
            ->order('created_dt', 'DESC')
            ->limit(0, 5)
            ->execute()
            ->all()
            ->getResult();

        foreach($reviews as $review){
            $review->getDayString($today, $yesterday);
        }

        $this->design->assign('reviews', $reviews);


        $callbacks = (new Callback())
            ->query()
            ->with(['users'])
            ->select()
            ->where('moderated = 0')
            ->order('created_dt', 'DESC')
            ->limit(0, 5)
            ->execute()
            ->all()
            ->getResult();

        if($callbacks){
            foreach($callbacks as $callback){
                $callback->getDayString($today, $yesterday);
            }
        }

        $this->design->assign('callbacks', $callbacks);

        return Response::html($this->render(SYSTEM_TPL . '/' . $this->getCore()->tpl_admin_path .'/html/main.tpl'));
    }

    public function typography()
    {
        return Response::html($this->render(SYSTEM_TPL . '/' . $this->getCore()->tpl_admin_path .'/html/typography.tpl'));
    }

}