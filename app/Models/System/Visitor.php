<?php
namespace app\model;

use App\Layers\LayerDatabaseModel;

class Visitor extends LayerDatabaseModel
{
    protected $table = 'visitors';

    public function join()
    {

    }

    public function beforeInsert()
    {
        $ip_addres_long = ip2long($this->core->remote_ip);

        $ip = (new Ip)->query->select()->where('ip = :ip', [':ip' => $ip_addres_long])->limit()->execute()->one()->getResult();

        if($ip == null){
            $ip = (new Ip);
            $ip->ip = $ip_addres_long;
            $ip->insert();

            $this->ip_id = $ip->id;
        }else{
            $this->ip_id = $ip->id;
        }

        if($this->core->auth->is_auth){
            $this->user_id = $this->core->auth->user->id;
        }

        $this->created_at = time();
    }
}