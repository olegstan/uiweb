<?
namespace app\model;

use app\layer\LayerModel;
use core\behavior\Rules;
use core\helper\Mail;
use core\helper\Response;

class FormBackcall extends LayerModel implements Rules
{
    protected $table = 'form-backcall';

    public function rules($scenario)
    {
        return [
            ['field' => 'name', 'filter' => 'trim'],
            ['field' => 'country_id', 'filter' => 'trim'],
            ['field' => 'phone', 'filter' => 'trim'],
        ];
    }

    public function validateRules($scenario)
    {
        return [
            ['field' => 'name', 'property' => 'name', 'rule' => 'empty', 'msg' => 'Напишите как к вам можно обращаться'],
            ['field' => 'phone', 'property' => 'phone', 'rule' => 'dynamic_pattern', 'class_name' => 'Country', 'class_id' => $this->country_id, 'msg' => 'Телефон не соответствует формату'],
            ['field' => 'phone', 'property' => 'phone', 'rule' => 'empty', 'msg' => 'Напишите номер телефона и мы позвоним вам'],
            ['field' => 'phone', 'property' => 'country_id', 'rule' => 'empty', 'msg' => 'Выберите страну'],
        ];
    }

    public function join()
    {

    }

    public function insert()
    {
        if (count($this->validate())) {
            return ['result' => 'error', 'error' => 'Некоторые поля формы заполенены неправильно.', 'success' => $this->validation->a_success, 'errors' => $this->validation->a_errors];
        } else {
            $this->beforeInsert();

            $this->query->insert($this)->execute();

            $this->afterInsert();

            (new Mail())->send('olegstan@inbox.ru', 'Пришла заявка', 'feedback');

            Response::json(['result' => 'success', 'success' => 'Мы получили ваш заказ и в ближайшее время мы свяжемся с вами']);
        }
    }

    public function beforeInsert()
    {
        $this->created_at = time();
    }

    public function afterInsert()
    {
        //Mail::feedback('olegstan@inbox.ru');
    }
}
