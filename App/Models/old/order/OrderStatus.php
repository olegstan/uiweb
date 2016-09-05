<?php
namespace app\models\order;

use app\layer\LayerModel;

use app\models\order\Order;
use core\Collection;
use \DateTime;

class OrderStatus extends LayerModel
{
    protected $table = 'mc_orders_statuses';

    public $orders = [];

    public static function orders(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $order_status = $collection->getResult();

                if($order_status){
                    $orders = (new Order())
                        ->query()
                        ->with(['purchases', 'users'])
                        ->select()
                        ->where('status_id = :status_id AND moderated = 0', [':status_id' => $order_status->id])
                        ->order('created_dt', 'DESC')
                        ->limit(0, 5)
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();


                    if($orders){
                        $today = new DateTime();
                        $yesterday = (new DateTime())->modify('-1 day');

                        foreach($orders as $order){
                            $order->getDayString($today, $yesterday);
                        }

                        $order_status->orders = $orders;
                    }
                }
                break;
            case 'all':

                break;
        }
    }
}