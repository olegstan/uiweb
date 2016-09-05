<?php
namespace Framework\Validation;

//URL multicheckbox unique
class Validation
{
    /**
     * @var array
     */
    public $a_errors = [];
    /**
     * @var array
     */
    public $a_success = [];

    /**
     * @param array $validate_rules
     */
    public function __construct(array $validate_rules)
    {
        if ($validate_rules) {
            foreach ($validate_rules as $rules) {
                if (isset($rules['field']) && is_string($rules['rule'])) {
                    switch ($rules['rule']) {
                        case 'required':
                            $this->validateRequired($this->$rules['property'], $rules['field'], $rules['msg']);
                            break;
                        case 'not_empty':
                            $this->validateNotEmpty($this->$rules['property'], $rules['field'], $rules['msg']);
                            break;
                        case 'integer':
                            $this->validateInteger($this->$rules['property'], $rules['field'], $rules['msg']);
                            break;
                        case 'string':
                            $this->validateString($this->$rules['property'], $rules['field'], $rules['msg']);
                            break;
                        case 'max_length':
                            $this->validateMaxLength($this->$rules['property'], $rules['field'], $rules['length'], $rules['msg']);
                            break;
                        case 'min_length':
                            $this->validateMinLength($this->$rules['property'], $rules['field'], $rules['length'], $rules['msg']);
                            break;
                        case 'max_value':
                            $this->validateMaxValue($this->$rules['property'], $rules['field'], $rules['max_value'], $rules['msg']);
                            break;
                        case 'min_value':
                            $this->validateMinValue($this->$rules['property'], $rules['field'], $rules['min_value'], $rules['msg']);
                            break;
                        case 'email':
                            $this->validateEmailPattern($this->$rules['property'], $rules['field'], $rules['msg']);
                            break;
                        case 'unique':
                            $this->validateUniqueField($this->$rules['property'], $rules['field'], $rules['class_name'], $rules['class_field'], $rules['table_field'], $rules['msg']);
                            break;
                        case 'unique_fields':
                            $validate_property = [];
                            foreach ($rules['property'] as $property) {
                                $validate_property[] = $this->$property;
                            }
                            $this->validateUniqueFields($validate_property, $rules['field'], $rules['class_name'], $rules['class_fields'], $rules['table_fields'], $rules['msg']);
                            break;
                        case 'pattern':
                            $this->validatePattern($this->$rules['property'], $rules['field'], $rules['pattern'], $rules['msg']);
                            break;
                        case 'dynamic_pattern':
                            $this->validateDynamicPattern($this->$rules['property'], $rules['field'], $rules['class_name'], $rules['class_id'], $rules['msg']);
                            break;
                        case 'compare':
                            $this->validateCompare($this->$rules['property'], $this->$rules['another_property'], $rules['field'], $rules['another_field'], $rules['msg']);
                            break;
                    }
                }
            }
        }
        $this->a_success = array_diff_key($this->a_success, $this->a_errors);
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     * @param string $success_message
     */
    public function validateInteger($value, $keyname, $error_message = '', $success_message = '')
    {
        if(!filter_var($value, FILTER_VALIDATE_INT)){
            return $this->a_errors[$keyname] = $error_message;
        }else{
            return $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateString($value, $keyname, $error_message = '', $success_message = '')
    {
        if (!is_string($value)) {
            return $this->a_errors[$keyname] = $error_message;
        }else{
            return $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateRequired($value, $keyname, $error_message = '', $success_message = '')
    {
        if(!isset($value)){
            return $this->a_errors[$keyname] = $error_message;
        }else{
            return $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     */

    public function validateNotEmpty($value, $keyname, $error_message = '', $success_message = '')
    {
        if(is_array($value) && empty($value)){
            return $this->a_errors[$keyname] = $error_message;
        }else if(is_string($value) || is_int($value)) {
            $value = trim((string)$value);
            if (empty($value) && $value !== '0') {
                return $this->a_errors[$keyname] = $error_message;
            }
        }
        return $this->a_success[$keyname] = $success_message;
    }

    /**
     * @param $value
     * @param $keyname
     * @param $pattern
     * @param string $error_message
     */

    public function validatePattern($value, $keyname, $pattern, $error_message = '', $success_message = '')
    {
        if (!preg_match('#^' . $pattern . '$#', $value)) {
            return $this->a_errors[$keyname] = $error_message;
        }else{
            return $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param $pattern
     * @param string $error_message
     */

    public function validateDynamicPattern($value, $keyname, $class_name, $class_id, $error_message = '', $success_message = '')
    {
        $class_name = 'app\\model\\' . $class_name;
        $model = new $class_name;
        $model = $model->query->select()->where('`' . $model->table . '`.`id` = :id', [':id' => $class_id])->execute()->one()->getResult();

        if (!preg_match('#^' . $model->pattern . '$#', $value)) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateMaxValue($value, $keyname, $max_value, $error_message = '', $success_message = '')
    {

        //if(!filter_var($value, FILTER_VALIDATE_INT)){
        //filter_var($int, FILTER_VALIDATE_INT, array("options" => array("min_range"=>$min, "max_range"=>$max))

        if (!is_numeric($value) && $value > $max_value) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateMinValue($value, $keyname, $min_value, $error_message = '', $success_message = '')
    {
        //filter_var($int, FILTER_VALIDATE_INT, array("options" => array("min_range"=>$min, "max_range"=>$max))


        if (!is_numeric($value) && $value < $min_value) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateBetween($value, $keyname, $min_value, $max_value, $error_message = '', $success_message = '')
    {
        //filter_var($int, FILTER_VALIDATE_INT, array("options" => array("min_range"=>$min, "max_range"=>$max))


        if($min_value < $value && $value < $max_value){
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param $length
     * @param string $error_message
     */

    public function validateMaxLength($value, $keyname, $length, $error_message = '', $success_message = '')
    {
        if (Text::strlenUtf8(Text::toUtf8($value)) > $length) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param $length
     * @param string $error_message
     */

    public function validateMinLength($value, $keyname, $length, $error_message = '', $success_message = '')
    {
        if (Text::strlenUtf8(Text::toUtf8($value)) > $length) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param $field
     * @param bool $current_value
     * @param string $error_message
     */

    public function validateUniqueField($value, $keyname, $class_name, $class_field, $table_field, $error_message = '������ �������� �� ���������', $success_message = '', $current_value = false)
    {
        /**
         *   namespace
         */
        $model = new $class_name();
        $model = $model->query()->select()->where($class_field . ' = :' . $class_field, [':' . $class_field => $value])->execute()->one()->getResult();
        if ($model !== null) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    public function validateUniqueFields($values, $keyname, $class_name, $class_fields, $table_fields, $error_message = '������ �������� �� ���������', $success_message = '')
    {
        $class_name = 'app\\model\\' . $class_name;
        $model = new $class_name;

        $condition = '';
        $and = '';

        foreach ($class_fields as $k => $field) {
            if($condition){
                $and = ' AND ';
            }

            $condition .= $and . $table_fields[$k] . ' = :' . $field;
            $bind[':' . $field] = $values[$k];
        }

        $model = $model->query->select()->where($condition, $bind)->execute()->one()->getResult();

        if ($model !== null) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     */

    public function validateEmailPattern($value, $keyname, $error_message = '', $success_message = '')
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     */

    public function validateIP4Pattern($value, $keyname, $error_message = '', $success_message = '')
    {
        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     */

    public function validateIP6Pattern($value, $keyname, $error_message = '', $success_message = '')
    {
        if (!filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }

    /**
     * @param $value1
     * @param $value2
     * @param $keyname1
     * @param $keyname2
     * @param string $error_message
     */

    public function validateCompare($value1, $value2, $keyname1, $keyname2, $error_message = '', $success_message = '')
    {
        if (strcmp($value1, $value2) !== 0) {
            $this->a_errors[$keyname1] = $error_message;
            $this->a_errors[$keyname2] = $error_message;
        }else{
            $this->a_success[$keyname1] = $success_message;
            $this->a_success[$keyname2] = $success_message;
        }
    }

    /**
     * @param $value
     * @param $keyname
     * @param string $error_message
     */

    function validateCheckbox($value, $keyname, $error_message = '', $success_message = '')
    {
        if ($_REQUEST[$keyname] != $value) {
            $this->a_errors[$keyname] = $error_message;
        }else{
            $this->a_success[$keyname] = $success_message;
        }
    }



    /*public function validateName($keyname, $current_name)
    {
        $name = strip_tags(trim($_REQUEST[$keyname]));

        $this->validateEmpty($name, $keyname);
        $this->validatePattern($name, $keyname, '#^[0-9a-zA-Z_ ]+$#', '�� ����������� ��������� �������');
        $this->validateLength($name, $keyname, 32);
        $this->validateUnique($name, $keyname, '_name', $current_name, '������������ � ����� ������� ��� ���������������');
    }

    function validateEmail($keyname, $current_email)
    {
        $email = strip_tags(trim($_REQUEST[$keyname]));

        $this->validateUnique($email, $keyname, 'email', $current_email, '������������ � ����� ����������� ������ ��� ����������');
        $this->validateLength($email, $keyname, 32);
        $this->validateEmailPattern($email, $keyname);
        $this->validateEmpty($email, $keyname);
    }*/

    /*function validatePhone($keyphone, $current_phone)
    {
        $phone = strip_tags(trim($_REQUEST[$keyphone]));

        $this->validateUnique($phone, $keyphone, 'phone_preferred', $current_phone, '������������ � ����� ��������� ��� ����������');
        $this->validatePattern($phone, $keyphone, '#^\+7\-[0-9]{3}\-[0-9]{3}\-[0-9]{2}\-[0-9]{2}$#', '������� �� ������������� �������. ������: +7-964-789-01-25');
        $this->validateEmpty($phone, $keyphone);
    }*/

    /*function validatePassword($keyname1, $keyname2)
    {
        $password = strip_tags(trim($_REQUEST[$keyname1]));
        $password_repeat = strip_tags(trim($_REQUEST[$keyname2]));

        $this->validateEmpty($password, $keyname1, '����������, ������� ������');
        $this->validateCompare($password, $password_repeat, $keyname1, $keyname2);
    }*/


    /*function validateRegistration($keyname, $keyemail, $keypassword, $keypassword_repeat, $checkbox)
    {
        $this->validateName($keyname);
        $this->validateEmail($keyemail);
        $this->validatePassword($keypassword, $keypassword_repeat);
        //$this->validateCheckbox($checkbox);

        return $this->a_errors;
    }*/

    /*function validateUpdateProfile($keyname, $keyemail, $keyphone)
    {
        $this->validateName($keyname, $user->_name);
        $this->validateEmail($keyemail, $user->email);
        $this->validatePhone($keyphone, $user->phone_preferred);

        return $this->a_errors;
    }

    function validateEmailAndPhone($keyemail, $keyphone)
    {
        $this->validateEmail($keyemail, $user->email);
        $this->validatePhone($keyphone, $user->phone_preferred);

        return $this->a_errors;
    }*/

}