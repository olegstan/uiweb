<?
namespace app\model\Good;

use app\layer\LayerModel;

class Good extends LayerModel
{
    public function join()
    {

    }


    public function ajaxMethod($array)
    {

        $this->load($array, $array['action']);

        // проверяем есть ли такой товар уже

        switch ($array['action']) {
            case 'add_to_cart':
                //$result = $this->insert();
                break;
            case 'delete_from_cart':

                //$result = $this->update();
                break;
            case 'change_amount':
                //$result = $this->delete();
                break;
            case 'clear_cart':
                //$result = $this->delete();
                break;
        }
        //return json_encode($result);
    }
}