<?php
namespace Uiweb\Auth\User;

//use app\layer\LayerModel;

class UserGroup
{
    protected $table = 'mc_access_groups';

    public $group_name;

    public function afterSelect()
    {
        $this->group_name = $this->group_name ? $this->group_name : $this->name;

        return $this;
    }
}
