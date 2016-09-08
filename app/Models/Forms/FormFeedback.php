<?
namespace app\model;

use app\layer\LayerModel;
use core\behavior\Rules;
use core\helper\Mail;
use core\helper\Response;

class FormFeedback extends LayerModel implements Rules
{
    protected $table = 'form-feedbacks';

    public function rules($scenario)
    {
        return [
            ['field' => 'name', 'filter' => 'trim'],
            ['field' => 'email', 'filter' => 'trim'],
            ['field' => 'country_id', 'filter' => 'trim'],
            ['field' => 'phone', 'filter' => 'trim'],
            ['field' => 'topic'],
            ['field' => 'text', 'filter' => 'trim'],
        ];
    }

    public function validateRules($scenario)
    {
        return [
            ['field' => 'name', 'property' => 'name', 'rule' => 'empty', 'msg' => 'Напишите как к вам можно обращаться'],
            ['field' => 'email', 'property' => 'email', 'rule' => 'email', 'msg' => 'Адрес электронной почты не соответствует формату'],
            ['field' => 'email', 'property' => 'email', 'rule' => 'max_length', 'length' => 253, 'msg' => 'Адрес электронной почты не может содержать больше 320 символов'],
            ['field' => 'email', 'property' => 'email', 'rule' => 'empty', 'msg' => 'Напишите адрес электронной почты и мы напишем вам'],
            ['field' => 'phone', 'property' => 'phone', 'rule' => 'dynamic_pattern', 'class_name' => 'Country', 'class_id' => $this->country_id, 'msg' => 'Телефон не соответствует формату'],
            ['field' => 'phone', 'property' => 'phone', 'rule' => 'empty', 'msg' => 'Напишите номер телефона и мы позвоним вам'],
            ['field' => 'phone', 'property' => 'country_id', 'rule' => 'empty', 'msg' => 'Выберите страну'],
            ['field' => 'topic[]', 'property' => 'topic', 'rule' => 'empty', 'msg' => 'Выберите тему вопроса'],
            ['field' => 'text', 'property' => 'text', 'rule' => 'empty', 'msg' => 'Расскажите нам о своём проекте']
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
        $topic_values = ['website', 'apps', 'seo'];

        foreach ($this->topic as $k => $v) {
            $this->topic[$k] = str_replace($v, array_search($v, $topic_values), $v);
        }
        $this->topic = implode(',', $this->topic);
        $this->created_at = time();
    }

    public function afterInsert()
    {
        //Mail::feedback('olegstan@inbox.ru');
    }
}
