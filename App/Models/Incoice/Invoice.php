<?php
namespace App\Models\Invoice;

use App\Layers\LayerDatabaseModel;

/**
 * Class Invoice
 *
 * @property integer $number
 * @property integer $customer_company_id
 * @property integer $provider_company_id
 * @property string $created_at
 * @property string $modified_at
 *
 * @package App\Models
 */
class Invoice extends LayerDatabaseModel
{
    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'customer_company_id',
        'provider_company_id',
        'created_at',
        'modified_at',
    ];

//    /**
//     * @var Company
//     */
//    public function customer()
//    {
//        return $this->hasOne('App\Models\Company', 'id', 'customer_company_id');
//    }
//    /**
//     * @var Company
//     */
//    public function provider()
//    {
//        return $this->hasOne('App\Models\Company', 'id', 'provider_company_id');
//    }
//
//    public function goods()
//    {
//        return $this->hasMany('App\Models\InvoicesGood', 'invoice_id', 'id')->orderBy('position');
//    }

    public function getInvoiceNumberWithDate()
    {
        $day = date('d', strtotime($this->created_at));
        $month = Month::getMonth(date('m', strtotime($this->created_at)));
        $year = date('Y', strtotime($this->created_at)) . ' г.';
        return 'СЧЕТ НА ОПЛАТУ № ' . $this->number . ' от ' . $day . ' ' . $month . ' ' . $year;
    }

    public function saveGoods(array $goods = [])
    {
        foreach ($goods as $good) {
            if(!(new InvoicesGood(array_merge($good, ['invoice_id' => $this->id])))->save()){
                return false;
            }
        }
        return true;
    }

    public function toExcel()
    {

    }

//    public function updateGoods(array $goods = [])
//    {
//        foreach ($goods as $good) {
//            $obj = InvoicesGood::where('id', '=', $good['id'])->first();
//            if($obj){
//                if(!$obj->update(array_merge($good, ['invoice_id' => $this->id]))){
//                    return false;
//                }
//            }else{
//                if(!(new InvoicesGood(array_merge($good, ['invoice_id' => $this->id])))->save()){
//                    return false;
//                }
//            }
//        }
//        return true;
//    }

    public static $columns = [
        'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
        'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
        'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK',
        'AL'
    ];

    public static $heights = [
        1 => 60,
        2 => 36,
        3 => 11.5,
        4 => 13,
        5 => 11,
        6 => 11,
        7 => 13,
        8 => 13,
        9 => 12,
        10 => 11,
        11 => 11.5,
        12 => 36,
        13 => 11.5,
        14 => 13,
        15 => 45,
        16 => 45,
        17 => 13
    ];

    public static $coords = [
        'B4:S6',
        'T4:V4',
        'T5:V6',
        'W4:AL6',
        'B7:C7',
        'D7:J7',
        'M7:S7',
        'K7:S7',
        'B8:S10',
        'T7:V10',
        'W7:AL10'
    ];

    public static $print = true;

    public static function get(Invoice $invoice, $type = 'xls')
    {
        return self::invoice(mb_strtolower(str_replace([' '], '_', $invoice->getInvoiceNumberWithDate()), 'utf-8'), $invoice, $type);
    }

    public static function setPrint($value)
    {
        self::$print = (bool) $value;
    }

    public static function getPrint()
    {
        return self::$print;
    }

    public static function invoice($filename, Invoice $invoice, $type)
    {
        Excel::create($filename, function ($excel) use ($invoice) {

            $excel->getDefaultStyle()->getFont()->setName('Arial');

            $excel->sheet('Лист1', function ($sheet) use ($invoice) {

                $last_row = self::invoiceHeader($sheet, $invoice, 0);

                $last_row = self::invoiceBody($sheet, $invoice, $last_row);

                $last_row = self::invoiceFooter($sheet, $invoice, $last_row);

            });
        })->store($type, storage_path('excel/inovices'))->export($type);

//        File::move(storage_path('excel/exports') . $filename . '.xls', mb_str_replace([' '], '_', $invoice->getInvoiceNumberWithDate()));

        return true;
    }

    /**
     * @param $sheet Maatwebsite\Excel\Classes\LaravelExcelWorksheet
     * @param $invoice
     */
    public static function invoiceHeader($sheet, $invoice, $start_row)
    {
        $sheet->setPageMargin([
            0.30, 0.30, 0.30, 0.30
        ]);

        //$sheet->getPageSetup()->setOrientation(\PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        //$sheet->getPageSetup()->SetPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);

        $sheet->setAutoSize(true);

        foreach (self::$heights as $k => $height) {
            $sheet->setHeight($k, $height);
        }


        // Шапка
        //$sheet->setOrientation('landscape');
        //объединяем ячейки

        $sheet->setWidth('A', 0);

        foreach(self::$columns as $column){
            $sheet->setWidth($column, 2.6);
        }

        $sheet->mergeCells('B1:AL1');
        $sheet->cells('B1:AL1', function ($cells) {
            $cells->setAlignment('center');
            $cells->setVAlignment('center');
            $cells->setFontSize(26);
        });
        $sheet->cell('B1', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->name);
        });

        $sheet->mergeCells('B2:AL2');
        $sheet->getStyle('B2:AL2')->getAlignment()->setWrapText(true);
        $sheet->cells('B2:AL2', function ($cells) {
            $cells->setAlignment('center');
            $cells->setValignment('top');
            $cells->setFontSize(8);
        });
        $sheet->cell('B2', function($cell){
            $cell->setValue('Данный счет (оферта) действителен в течение 10 (десяти) банковских дней с момента выставления. По истечении указанного срока и отсутствии оплаты выполненных работ Заказчик выплачивает Платеж по счету производится только организацией, указанной в позиции "Плательщик". Оплата от третьих лиц не принимается. НДС оплачивать строго по выставленному счету.');
        });

        //for pdf
        $sheet->cells('B3:AL23', function ($cells) {
            $cells->setBorder('none', 'none', 'none', 'none');
        });

        $sheet->mergeCells('B4:S5');
        $sheet->cell('B4', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->bank_name);
        });

        $sheet->cells('B4:AL10', function ($cells) {
            $cells->setAlignment('left');
            $cells->setFontSize(10);
            $cells->setVAlignment('top');
        });
//
        $sheet->mergeCells('B6:S6');
        $sheet->cell('B6', function($cell){
            $cell->setValue('Банк получателя');
        });
        $sheet->cells('B6', function ($cells) {
            $cells->setFontSize(8);
        });

        $sheet->mergeCells('B7:C7');
        $sheet->cell('B7', function($cell){
            $cell->setValue('ИНН');
        });

        $sheet->mergeCells('D7:J7');
        $sheet->cell('D7', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->inn);
        });

        $sheet->mergeCells('K7:L7');
        $sheet->cell('K7', function($cell){
            $cell->setValue('КПП');
        });

        $sheet->mergeCells('M7:S7');
        $sheet->cell('M7', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->kpp);
        });

        $sheet->getStyle('B8:S9')->getAlignment()->setWrapText(true);
        $sheet->mergeCells('B8:S9');
        $sheet->cell('B8', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->name);
        });

        $sheet->mergeCells('B10:S10');
        $sheet->cell('B10', function($cell){
            $cell->setValue('Получатель');
        });

        $sheet->cells('B10', function ($cells) {
            $cells->setFontSize(8);
        });

        $sheet->mergeCells('T4:V4');
        $sheet->cell('T4', function($cell){
            $cell->setValue('БИК');
        });

        $sheet->mergeCells('W4:AL4');
        $sheet->cell('W4', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->bik);
        });
        $sheet->cells('W4', function ($cells) {
            $cells->setAlignment('right');
        });

        $sheet->mergeCells('T5:V6');
        $sheet->cell('T5', function($cell){
            $cell->setValue('К. Сч. №');
        });

        $sheet->mergeCells('W5:AL6');
        $sheet->cell('W5', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->	correspondent_account);
        });
        $sheet->cells('W5', function ($cells) {
            $cells->setAlignment('right');
        });

        $sheet->mergeCells('T7:V10');
        $sheet->cell('T7', function($cell){
            $cell->setValue('Р. Сч. №');
        });

        $sheet->mergeCells('W7:AL10');
        $sheet->cell('W7', function($cell) use ($invoice){
            $cell->setValue($invoice->provider->	operating_account);
        });
        $sheet->cells('W7', function ($cells) {
            $cells->setAlignment('right');
        });

        /**
         * рисуем границы для шапки
         */

        foreach(self::$coords as $coord){
            $sheet->cells($coord, function ($cells) {
                $cells->setBorder('thin', 'thin', 'thin', 'thin');
            });
        }

        $sheet->mergeCells('B12:AL12');
        $sheet->cells('B12:AL12', function ($cells) {
            $cells->setAlignment('left');
        });
        $sheet->cell('B12', function($cell) use($invoice){
            $cell->setValue($invoice->getInvoiceNumberWithDate());
        });
        $sheet->cells('B12', function ($cells) {
            $cells->setAlignment('left');
            $cells->setFontSize(14);
            $cells->setFontWeight('bold');
            $cells->setVAlignment('center');
        });

        $sheet->cells('B12:AL12', function ($cells) {
            $cells->setBorder('none', 'none', 'thick', 'none');
        });

        $sheet->mergeCells('B15:G15');
        $sheet->cell('B15', function($cell){
            $cell->setValue('Исполнитель:');
        });
        $sheet->cells('B15', function ($cells) {
            $cells->setAlignment('left');
            $cells->setValignment('top');
        });

        $sheet->mergeCells('H15:AL15');
        $sheet->getStyle('H15:AL15')->getAlignment()->setWrapText(true);
        $sheet->cell('H15', function($cell) use($invoice) {
            $cell->setValue($invoice->provider->name . ', ИНН ' . $invoice->provider->inn . ', КПП ' . $invoice->provider->kpp . ', ' . $invoice->provider->jur_address);
        });
        $sheet->cells('B15:AL15', function ($cells) {
            $cells->setFontSize(12);
            $cells->setAlignment('left');
            $cells->setValignment('top');
        });

        $sheet->mergeCells('B16:G16');
        $sheet->cell('B16', function($cell){
            $cell->setValue('Заказчик:');
        });
        $sheet->cells('B16', function ($cells) {
            $cells->setAlignment('left');
            $cells->setValignment('top');
            $cells->setFontSize(12);
        });

        $sheet->mergeCells('H16:AL16');
        $sheet->getStyle('H16:AL16')->getAlignment()->setWrapText(true);
        $sheet->cell('H16', function($cell) use($invoice){
            $cell->setValue($invoice->customer->name . ', ИНН ' . $invoice->customer->inn . ', КПП ' . $invoice->customer->kpp . ', ' . $invoice->customer->jur_address);
        });
        $sheet->cells('H16:AL16', function ($cells) {
            $cells->setFontSize(12);
            $cells->setAlignment('left');
            $cells->setValignment('top');
        });

        /**
         * позиции
         */

        $sheet->mergeCells('B18:C18');
        $sheet->cell('B18', function($cell){
            $cell->setValue('№');
        });

        $sheet->cells('B18:C18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('D18:X18');
        $sheet->cell('D18', function($cell){
            $cell->setValue('Товары (работы, услуги)');
        });

        $sheet->cells('D18:X18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->cells('D18:X18', function ($cells) {
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('Y18:AA18');
        $sheet->cell('Y18', function($cell){
            $cell->setValue('Кол-во');
        });

        $sheet->cells('Y18:AA18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AB18:AC18');
        $sheet->cell('AB18', function($cell){
            $cell->setValue('Ед.');
        });

        $sheet->cells('AB18:AC18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AD18:AG18');
        $sheet->cell('AD18', function($cell){
            $cell->setValue('Цена');
        });

        $sheet->cells('AD18:AG18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AH18:AL18');
        $sheet->cell('AH18', function($cell){
            $cell->setValue('Сумма');
        });

        $sheet->cells('AH18:AL18', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->cells('B18:AL18', function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setAlignment('center');
            $cells->setFontSize(10);
        });

        return 18;
    }

    public static function invoiceBody($sheet, $invoice, $start_row)
    {
        $row = $start_row + 1;
        //для бордера
        $last_row = $start_row;

        $count = 0;
        $sum = 0;

        if(count($invoice->goods) > 0) {
            foreach ($invoice->goods as $k => $element) {
                $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
                    $cells->setFontSize(8);
                });

                $sheet->mergeCells('B' . $row . ':C' . $row);
                $sheet->cell('B' . $row, function ($cell) use ($element, $count) {
                    $cell->setValue($count + 1);
                });
                $sheet->cells('B' . $row, function ($cells) {
                    $cells->setAlignment('center');
                });

                $sheet->mergeCells('D' . $row . ':X' . $row);
                $sheet->cell('D' . $row, function ($cell) use ($element) {
                    $cell->setValue($element->name);
                });

                $sheet->cells('D' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $sheet->mergeCells('Y' . $row . ':AA' . $row);
                $sheet->cell('Y' . $row, function ($cell) use ($element) {
                    $cell->setValue($element->count);
                });

                $sheet->cells('Y' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $sheet->mergeCells('AB' . $row . ':AC' . $row);
                $sheet->cell('AB' . $row, function ($cell) use ($element) {
                    $cell->setValue($element->unit->name);
                });

                $sheet->cells('AB' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $sheet->mergeCells('AD' . $row . ':AG' . $row);
                $sheet->cell('AD' . $row, function ($cell) use ($element) {
                    $cell->setValue($element->price);
                });
                $sum += $element->price * $element->count;

                $sheet->cells('AD' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });


                $sheet->mergeCells('AH' . $row . ':AL' . $row);
                $sheet->cell('AH' . $row, function ($cell) use ($element, $row) {
                    $cell->setValue('=PRODUCT(Y' . $row . ', AD' . $row . ')');
                });

                $sheet->cells('AH' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
                    $cells->setBorder('thin', 'thin', 'thin', 'thin');
                });

                $sheet->setHeight($row, 11);

                $last_row = $row;
                $row++;
                $count++;
            }
        }

        /**
         * обводка позиций
         */
        $sheet->cells('B' . $start_row . ':AL' . $last_row, function ($cells) {
            $cells->setBorder('thick', 'thick', 'thick', 'thick');
        });

        $sheet->setHeight($row, 7);
        $row++;
//
        /*$sheet->cells('B' . $row . ':AL' . ($row + 2), function ($cells) {
            $cells->setAlignment('right');
            $cells->setFontWeight('bold');
        });*/
//
        $sheet->mergeCells('AD' . $row . ':AG' . $row);
        $sheet->cell('AD' . $row, function ($cell){
            $cell->setValue('Итого:');
        });
//
        $sheet->cells('AD' . $row . ':AG' . $row, function ($cells) {
            $cells->setAlignment('right');
            $cells->setFontSize(10);
        });

        $sheet->mergeCells('AH' . $row . ':AL' . $row);
        $sheet->cell('AH' . $row, function ($cell) use ($sum) {
            $cell->setValue($sum);
        });
        $sheet->cells('AH' . $row . ':AL' . $row, function ($cells) {
            $cells->setAlignment('center');
        });
        $sheet->setHeight($row, 13);
        $row++;

        $sheet->mergeCells('W' . $row . ':AG' . $row);
        $sheet->cell('W' . $row, function ($cell){
            $cell->setValue('Без НДС:');
        });
        $sheet->cells('W' . $row . ':AG' . $row, function ($cells) {
            $cells->setAlignment('right');
        });
        $sheet->mergeCells('AH' . $row . ':AL' . $row);
        $sheet->cell('AH' . $row, function ($cell){
            $cell->setValue('-');
        });
        $sheet->cells('AH' . $row . ':AL' . $row, function ($cells) {
            $cells->setAlignment('center');
            $cells->setFontSize(10);
        });

        $sheet->setHeight($row, 13);
        $row++;

        $sheet->mergeCells('W' . $row . ':AG' . $row);
        $sheet->cell('W' . $row, function ($cell){
            $cell->setValue('Всего к оплате:');
        });

        $sheet->cells('W' . $row . ':AG' . $row, function ($cells) {
            $cells->setAlignment('right');
            $cells->setFontSize(10);
        });


        $sheet->mergeCells('AH' . $row . ':AL' . $row);
        $sheet->cell('AH' . $row, function ($cell) use ($sum) {
            $cell->setValue($sum);
        });
        $sheet->cells('AH' . $row . ':AL' . $row, function ($cells) {
            $cells->setAlignment('center');
            $cells->setFontSize(10);
        });

        $sheet->setHeight($row, 13);
        $row++;

        $sheet->mergeCells('B' . $row . ':AL' . $row);

        $sheet->cell('B' . $row, function ($cell) use ($count, $sum, $last_row) {
            $cell->setValue('=CONCATENATE("Всего наименований ' . $count . ', на сумму ","' . $sum . '"," руб.")');
        });
        $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'none', 'none');
        });
        $sheet->setHeight($row, 13);
        $row++;

        $sheet->mergeCells('B' . $row . ':AL' . $row);
        $sheet->cell('B' . $row, function ($cell) use ($sum) {
            $cell->setValue((DigitToWord::toMoney($sum, 'mbUcfirst')));
        });
        $sheet->cells('B' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(10);
            $cells->setBorder('none', 'none', 'none', 'none');
        });

        $sheet->setHeight($row, 13);
        $row++;

        return $row;
    }

    public static function invoiceFooter($sheet, $invoice, $start_row)
    {
        $start_row++;
        $row = $start_row;
        $sheet->mergeCells('B' . $row . ':AL' . $row);
        $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'thick', 'none');
        });

        $row++;

        $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'none', 'none');
        });

        //set images

        if(self::getPrint()){
            $print = imagecreatefromgif(storage_path('documents/blue_print_noice.gif'));

            $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
            //$objDrawing->setName('Sample image');
            //$objDrawing->setDescription('Sample image');
            $objDrawing->setImageResource($print);
            $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF);
            $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(150);
            $objDrawing->setRotation(20);
            $objDrawing->setCoordinates('V' . $row);
            $objDrawing->setWorksheet($sheet);

            $signature = imagecreatefromgif(storage_path('documents/signature.gif'));

            $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
            $objDrawing->setImageResource($signature);
            $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF);
            $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setHeight(50);
            $objDrawing->setCoordinates('V' . ($row));
            $objDrawing->setWorksheet($sheet);
        }



        $row++;

        $sheet->mergeCells('B' . $row . ':F' . $row);
        $sheet->cell('B' . $row, function ($cell){
            $cell->setValue('Руководитель');
        });
        $sheet->cells('B' . $row . ':F' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
        });



        $sheet->mergeCells('H' . $row . ':P' . $row);
        $sheet->cell('H' . $row, function ($cell){
            $cell->setValue('Генеральный директор');
        });
        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $sheet->mergeCells('R' . $row . ':AA' . $row);
        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $sheet->mergeCells('AC' . $row . ':AL' . $row);
        $sheet->cell('AC' . $row, function ($cell) use ($invoice){
            $cell->setValue($invoice->provider->direktor);
        });
        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(10);
            $cells->setAlignment('center');
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $row++;

        $sheet->mergeCells('H' . $row . ':P' . $row);
        $sheet->cell('H' . $row, function ($cell){
            $cell->setValue('должность');
        });
        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('R' . $row . ':AA' . $row);
        $sheet->cell('R' . $row, function ($cell){
            $cell->setValue('подпись');
        });
        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('AC' . $row . ':AL' . $row);
        $sheet->cell('AC' . $row, function ($cell){
            $cell->setValue('расшифровка подписи');
        });
        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $row++;
        $row++;

        $sheet->mergeCells('B' . $row . ':F' . $row);
        $sheet->cell('B' . $row, function ($cell){
            $cell->setValue('Ответственный');
        });
        $sheet->cells('B' . $row . ':F' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
        });

        $sheet->mergeCells('H' . $row . ':P' . $row);
        $sheet->cell('H' . $row, function ($cell){
            $cell->setValue('Менеджер');
        });
        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $sheet->mergeCells('R' . $row . ':AA' . $row);
        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $sheet->mergeCells('AC' . $row . ':AL' . $row);
        $sheet->cell('AC' . $row, function ($cell) use ($invoice){
            $cell->setValue($invoice->provider->manager);
        });
        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(10);
            $cells->setAlignment('center');
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $row++;

        $sheet->mergeCells('H' . $row . ':P' . $row);
        $sheet->cell('H' . $row, function ($cell){
            $cell->setValue('должность');
        });
        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('R' . $row . ':AA' . $row);
        $sheet->cell('R' . $row, function ($cell){
            $cell->setValue('подпись');
        });
        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('AC' . $row . ':AL' . $row);
        $sheet->cell('AC' . $row, function ($cell){
            $cell->setValue('расшифровка подписи');
        });
        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        //for pdf
        $sheet->cells('A1:A' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'none', 'none');
        });

        return $row;
    }
}