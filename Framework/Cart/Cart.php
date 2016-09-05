<?php
namespace Framework\Cart;

use Framework\Cart\Drivers\CacheCart;
use Framework\Cart\Drivers\DatabaseCart;
use Framework\Cart\Drivers\SessionCart;
use Framework\Config;

class Cart
{
    /**
     * @return CacheCart|DatabaseCart|SessionCart
     */
    public static function getDriver()
    {
        switch(Config::get('cart', 'driver'))
        {
            case 'cache':
                return new CacheCart();
            case 'database':
                return new DatabaseCart();
            case 'session':
            default:
                return new SessionCart();
        }
    }



    /**
     * @param integer $id
     */
    public static function get($id)
    {
        return self::getDriver()->get($id);
    }

    /**
     * @param integer $id
     */
    public static function has($id)
    {
        return self::getDriver()->has($id);
    }

    /**
     * @return null
     */
    public static function getAll()
    {
        $ids = self::getDriver()->getAll();
        $products = [];
        if($ids){
            $class_name = Config::get('cart', 'product');
            $class = new $class_name;
            /**
             * Framework\Model\Types\DatabaseModel $class
             */
            $products = $class
                ->getQuery()
                ->select(['p.id', 'p.name', 'p.code', 'p.price', 'p.ipc2u_price', 'p.insat_price', 'p.url AS product_url', 'p.name', 'pc.url AS category_url'])
                ->from('products AS p')
                ->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
                ->where('p.id IN (' . implode(',', array_keys($ids)) . ')')
                ->execute()
                ->all('id')
                ->get();

            if($products){
                foreach($products as $k => $product){
                    $product->count = $ids[$k];
                    $product->sum = $ids[$k] * $product->price;
                }
            }
        }
        return $products;
    }
    /**
     * @param integer $id
     */
    public static function add($id)
    {
        self::getDriver()->add($id);
    }
    /**
     * @param integer $id
     */
    public static function delete($id)
    {
        self::getDriver()->delete($id);
    }
    /**
     * @param integer $id
     * @param integer $count
     */
    public static function change($id, $count)
    {
        self::getDriver()->change($id, $count);
    }

    /**
     *
     */
    public static function clear()
    {
        self::getDriver()->clear();
    }
}