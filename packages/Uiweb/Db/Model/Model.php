<?php
namespace Uiweb\Model;

use Uiweb\Validation\Interfaces\Validateble;
use Uiweb\Validation\ValidationTraits\ValidationTrait;

abstract class Model
{
    use ValidationTrait;
    /**
     * @var array
     */
    protected $fields = [];
    /**
     * @var array
     */
    protected $inserted_fields = [];
    /**
     * @var array
     */
    protected $updated_fields = [];
    /**
     * @var array
     */
    protected $guarded = [];
    /**
     * @var array
     */
    protected $fillable = [];

//    /**
//     * query debug
//     */
//
//    public $query_seconds = 0;

    /*static $instance;*/

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach ($this->fillable as $attribute) {
            if(isset($attributes[$attribute])){
                $this->{$attribute} = $attributes[$attribute];
            }
        }
    }

    /**
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    public static function self()
    {
//        return new self;
    }

    //protected $query;
    /**
     *
     */
    
    /*public function get($property)
    {
        return $this->$property;
    }*/

//    public static function getTable()
//    {
//        //return (new self)->table;
//    }



    /**
     * @return Redis
     */
    public function redis()
    {
        return new Redis();
    }

    abstract public function findById($id);

    abstract public function findByField($field, $value);

    abstract public function findAll();



//    /**
//     * @return array
//     */
//
//    public function findAll($rules = null, $key = false)
//    {
//        return $this->query()->select()->execute()->all($rules, $key);
//    }

//    /**
//     * @return $this
//     */
//
//    public function findOwn()
//    {
//        return $this->query()->select()->where('user_id = :user_id', [':user_id' => $this->core->auth->user->id])->execute();
//    }


    /**
     * @param bool $id
     * @return $this
     */
    public function save()
    {
        if(isset($this->id)){
            return $this->update();
        }else{
            return $this->insert();
        }
    }


    abstract function insert();

    abstract function update();

    abstract function delete();



//    public function updateOwn()
//    {
//        if ($this->core->auth->is_auth) {
//            $this->validate();
//
//            $this->query()->update($this)->where('id = :id AND user_id = :user_id', [':id' => $this->id, ':user_id' => $this->core->auth->user->id])->execute();
//
//            return ['result' => 'success', 'fields' => $this];
//        } else {
//            return ['result' => 'error', 'errors' => ['auth' => 'Пользователь не авторизован']];
//        }
//    }



//    /**
//     * @param $id
//     */
//
//    public function deleteOwn()
//    {
//        if ($this->core->auth->is_auth) {
//            $this->query()->delete()->where('id = :id AND user_id = :user_id', [':id' => $this->id, ':user_id' => $this->core->auth->user->id])->execute();
//            $this->afterDelete();
//            return ['result' => 'success', 'fields' => $this];
//        } else {
//            return ['result' => 'error', 'errors' => ['auth' => 'Пользователь не авторизован']];
//        }
//    }

    /**
     * @param null $rules
     * @return $this
     *
     * условие обработки селекта
     */

    public function beforeSelect($rules = null)
    {
        return $this;
    }

    public function afterSelect($rules = null)
    {
        return $this;
    }

    /**
     *
     */

    public function beforeInsert()
    {
        return $this;
    }

    /**
     *
     */

    public function afterInsert()
    {
        return $this;
    }

    public function beforeUpdate()
    {
        return $this;
    }

    /**
     *
     */

    public function afterUpdate()
    {
        return $this;
    }

    /**
     *
     */

    public function beforeValidate()
    {

    }

    /**
     *
     */

    public function afterValidate()
    {

    }

    /**
     *
     */

    public function afterDelete()
    {

    }

    /**
     *
     */

    public function beforeDelete()
    {

    }

//    /**
//     * @param $array
//     * @param null $scenario
//     * @return $this
//     */
//
//    public function load($array, $scenario = null)
//    {
//        $rules = $this->rules($scenario);
//
//        foreach ($rules as $rule) {
//            $this->$rule['field'] = $this->prepare($rule, $array[$rule['field']]);
//        }
//        return $this;
//    }
//
//    public function selectRules($scenario)
//    {
//        return [];
//    }
//
//
//    public function validateRules($scenario)
//    {
//        return [];
//    }

//    /**
//     * @param $array
//     * @param $value
//     * @return mixed
//     */
//
//    public function prepare($array, $value)
//    {
//        if (isset($array['field'])) {
//            if (is_array($array['filter'])) {
//                foreach ($array['filter'] as $filter) {
//                    $value = $array['filter'][$filter]($value);
//                }
//            } else if (is_string($array['filter'])) {
//                $value = $array['filter']($value);
//            }
//        }
//        return $value;
//    }
//
//    /**
//     * @param $array
//     * @return string
//     */
//
//    public function ajaxMethod($array)
//    {
//        $this->load($array, $array['action']);
//
//        switch ($array['action']) {
//            case 'insert':
//                $result = $this->insert();
//                break;
//            case 'update':
//                $result = $this->update();
//                break;
//            case 'delete':
//                $result = $this->delete();
//                break;
//        }
//
//
//        echo json_encode($result);
//    }
}
