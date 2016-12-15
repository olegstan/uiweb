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
        $reverse = array_reverse(self::$poolServiceProviders);
        foreach ($reverse as $provider){
            /**
             * @var ServiceProvider $provider
             */
            $provider->boot();
        }
        foreach ($reverse as $provider){
            /**
             * @var ServiceProvider $provider
             */
            $provider->register();
        }
    }

    /**
     * @return mixed
     */
    public static function getServiceProviders()
    {
        return Config::get('app.services.' . Request::getType());
    }

    /**
     * @var ServiceProvider[]
     */
    public static $poolServiceProviders = [];
    /**
     * @var array
     */
    public static $poolSingletons = [];

    /**
     * @param array $providers
     */
    public static function resolveDependencies($providers)
    {
        foreach ($providers as $provider){
            /**
             * @var string $provider
             */
            if(!isset(self::$poolServiceProviders[$provider])){
                self::$poolServiceProviders[$provider] = new $provider;
                self::resolveDependencies(self::$poolServiceProviders[$provider]->getDepencies());
            }
        }
    }
    
    public static function resolve($namespace)
    {
        if(!self::$poolSingletons[$namespace]){
            self::$poolSingletons[$namespace] = new $namespace;
        }
        return self::$poolSingletons[$namespace];
    }
}