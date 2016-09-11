<?php
namespace Uiweb;


class App
{
    /**
     *
     */
    public static function boot()
    {
        self::resolveDependencies(self::getServiceProviders());
        foreach (array_reverse(self::$pool) as $provider){
            /**
             * @var ServiceProvider $provider
             */
            $provider->boot();
        }
    }

    /**
     * @return mixed
     */
    public static function getServiceProviders()
    {
        return Config::get('app', 'services.' . Request::getType());
    }

    /**
     * @var ServiceProvider[]
     */
    public static $pool = [];

    /**
     * @param array $providers
     */
    public static function resolveDependencies($providers)
    {

        foreach ($providers as $provider){
            /**
             * @var string $provider
             */
            self::$pool[$provider] = new $provider;
            self::resolveDependencies(self::$pool[$provider]->getDepencies());
        }
    }
}