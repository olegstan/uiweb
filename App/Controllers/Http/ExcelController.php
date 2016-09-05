<?php
namespace App\Controllers\Http;

use App\Layers\LayerHttpController;
use App\Models\Act\Act;
use App\Models\Invoice\Invoice;
use Framework\Request\Types\HttpRequest;
use App\Layers\LayerView as View;
use Framework\Response\Types\HtmlResponse;

class ExcelController extends LayerHttpController
{
    public function getInvoice($id)
    {
        /**
         * @var Invoice
         */
        $invoice = (new Invoice())->findById($id)->get();

        if($invoice){
            return $invoice::toExcel();
        }else{
            return new HtmlResponse(new View('errors/404.php'), 404);
        }
    }

    public function getAct($id)
    {
        /**
         * @var Act
         */
        $act = (new Act())->findById($id)->get();

        if($act){
            return $act::toExcel();
        }else{
            return new HtmlResponse(new View('errors/404.php'), 404);
        }
    }
}