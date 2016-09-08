<?php
namespace App\Models\Act;

use App\Layers\LayerDatabaseModel;

/**
 * Class Act
 *
 * @property integer $number
 * @property integer $customer_company_id
 * @property integer $provider_company_id
 * @property string $created_at
 * @property string $modified_at
 *
 * @package App\Models
 */
class Act extends LayerDatabaseModel
{
    protected $table = 'acts';

    protected $fillable = [
        'number',
        'customer_company_id',
        'provider_company_id',
        'created_at',
        'modified_at',
    ];
//
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
//        return $this->hasMany('App\Models\ActsGood', 'act_id', 'id')->orderBy('position');
//    }
//
    public function getActNumberWithDate()
    {
        $day = date('d', strtotime($this->created_at));
        $month = Month::getMonth(date('m', strtotime($this->created_at)));
        $year = date('Y', strtotime($this->created_at)) . ' г.';
        return 'АКТ СДАЧИ-ПРИЕМКИ ВЫПОЛНЕННЫХ РАБОТ № ' . $this->number . ' от ' . $day . ' ' . $month . ' ' . $year;
    }

    public function toExcel()
    {

    }
//
//
//    public function saveGoods(array $goods = [])
//    {
//        foreach ($goods as $good) {
//            if(!(new ActsGood(array_merge($good, ['act_id' => $this->id])))->save()){
//                return false;
//            }
//        }
//        return true;
//    }
//
//    public function updateGoods(array $goods = [])
//    {
//        foreach ($goods as $good) {
//            $obj = ActsGood::where('id', '=', $good['id'])->first();
//            if($obj){
//                if(!$obj->update(array_merge($good, ['act_id' => $this->id]))){
//                    return false;
//                }
//            }else{
//                if(!(new ActsGood(array_merge($good, ['act_id' => $this->id])))->save()){
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
        2 => 11,
        3 => 36,
        4 => 36,
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
        15 => 11,
        16 => 11,
        17 => 11.5
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

    public static function get(Act $act, $type = 'xls')
    {
        return self::act(mb_strtolower(str_replace([' '], '_', $act->getActNumberWithDate()), 'utf-8'), $act, $type);
    }

    public static function setPrint($value)
    {
        self::$print = (bool) $value;
    }

    public static function getPrint()
    {
        return self::$print;
    }

    public static function act($filename, Act $act, $type)
    {
        Excel::create($filename, function ($excel) use ($act) {

            $excel->getDefaultStyle()->getFont()->setName('Arial');

            $excel->sheet('Лист1', function ($sheet) use ($act) {

                $last_row = self::actHeader($sheet, $act, 0);

                $last_row = self::actBody($sheet, $act, $last_row);

                $last_row = self::actFooter($sheet, $act, $last_row);

            });
        })->store($type, storage_path('excel/acts'))->export($type);

//        File::move(storage_path('excel/exports') . $filename . '.xls', mb_str_replace([' '], '_', $act->getNumberWithDate()));

        return true;
    }

    /**
     * @param $sheet Maatwebsite\Excel\Classes\LaravelExcelWorksheet
     * @param $act
     */
    public static function actHeader($sheet, $act, $start_row)
    {
        $sheet->setPageMargin([
            0.25, 0.30, 0.25, 0.30
        ]);

        //$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
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
            $cells->setFontSize(12);
            $cells->setFontWeight('bold');
        });
        $sheet->cell('B1', function($cell) use ($act){
            $cell->setValue($act->getActNumberWithDate());
        });

        $sheet->mergeCells('B3:G3');
        $sheet->cell('B3', function($cell) use ($act){
            $cell->setValue('Исполнитель:');
        });
        $sheet->cells('B3:G3', function ($cells) {
            $cells->setAlignment('center');
            $cells->setValignment('top');
            $cells->setFontSize(12);
        });

        $sheet->mergeCells('H3:AL3');
        $sheet->getStyle('H3:AL3')->getAlignment()->setWrapText(true);
        $sheet->cell('H3', function($cell) use ($act){
            $cell->setValue($act->provider->name . ', ИНН ' . $act->provider->inn . ', КПП ' . $act->provider->kpp . ', ' . $act->provider->jur_address);
        });
        $sheet->cells('H3:AL3', function ($cells) {
            $cells->setAlignment('left');
            $cells->setValignment('top');
            $cells->setFontSize(12);
        });

        $sheet->mergeCells('B4:G4');
        $sheet->cell('B4', function($cell) use ($act){
            $cell->setValue('Заказчик:');
        });
        $sheet->cells('B4:G4', function ($cells) {
            $cells->setAlignment('center');
            $cells->setValignment('top');
            $cells->setFontSize(12);
        });

        $sheet->mergeCells('H4:AL4');
        $sheet->getStyle('H4:AL4')->getAlignment()->setWrapText(true);
        $sheet->cell('H4', function($cell) use ($act){
            $cell->setValue($act->customer->name . ', ИНН ' . $act->customer->inn . ', КПП ' . $act->customer->kpp . ', ' . $act->customer->jur_address);
        });
        $sheet->cells('H4:AL4', function ($cells) {
            $cells->setAlignment('left');
            $cells->setValignment('top');
            $cells->setFontSize(12);
        });

        /**
         * позиции
         */

        $sheet->mergeCells('B6:C6');
        $sheet->cell('B6', function($cell){
            $cell->setValue('№');
        });

        $sheet->cells('B6:C6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('D6:X6');
        $sheet->cell('D6', function($cell){
            $cell->setValue('Товары (работы, услуги)');
        });

        $sheet->cells('D6:X6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->cells('D6:X6', function ($cells) {
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('Y6:AA6');
        $sheet->cell('Y6', function($cell){
            $cell->setValue('Кол-во');
        });

        $sheet->cells('Y6:AA6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AB6:AC6');
        $sheet->cell('AB6', function($cell){
            $cell->setValue('Ед.');
        });

        $sheet->cells('AB6:AC6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AD6:AG6');
        $sheet->cell('AD6', function($cell){
            $cell->setValue('Цена');
        });

        $sheet->cells('AD6:AG6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->mergeCells('AH6:AL6');
        $sheet->cell('AH6', function($cell){
            $cell->setValue('Сумма');
        });

        $sheet->cells('AH6:AL6', function ($cells) {
            $cells->setBorder('thin', 'thin', 'thin', 'thin');
        });

        $sheet->cells('B6:AL6', function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setAlignment('center');
            $cells->setFontSize(10);
        });

        return 6;
    }

    public static function actBody($sheet, $act, $start_row)
    {
        $row = $start_row + 1;
        //для бордера
        $last_row = $start_row;

        $count = 0;
        $sum = 0;

        if(count($act->goods) > 0) {
            foreach ($act->goods as $k => $element) {
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

    public static function actFooter($sheet, $act, $start_row)
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

//        if(self::getPrint()){
//            $print = imagecreatefromgif(storage_path('documents/blue_print_noice.gif'));
//
//            $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
//            //$objDrawing->setName('Sample image');
//            //$objDrawing->setDescription('Sample image');
//            $objDrawing->setImageResource($print);
//            $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF);
//            $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
//            $objDrawing->setHeight(150);
//            $objDrawing->setRotation(20);
//            $objDrawing->setCoordinates('V' . $row);
//            $objDrawing->setWorksheet($sheet);
//
//            $signature = imagecreatefromgif(storage_path('documents/signature.gif'));
//
//            $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
//            $objDrawing->setImageResource($signature);
//            $objDrawing->setRenderingFunction(\PHPExcel_Worksheet_MemoryDrawing::RENDERING_GIF);
//            $objDrawing->setMimeType(\PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
//            $objDrawing->setHeight(50);
//            $objDrawing->setCoordinates('V' . ($row));
//            $objDrawing->setWorksheet($sheet);
//        }

        $row++;

        $sheet->mergeCells('B' . $row . ':AL' . $row);
        $sheet->cell('B' . $row, function ($cell){
            $cell->setValue('Подписи сторон');
        });
        $sheet->cells('B' . $row . ':AL' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(12);
            $cells->setAlignment('center');
        });


        $row++;
        $row++;

        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cell('E' . $row, function ($cell){
            $cell->setValue('Исполнитель');
        });
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cell('X' . $row, function ($cell){
            $cell->setValue('Заказчик');
        });
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $row++;

        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cell('E' . $row, function ($cell) use ($act){
            $cell->setValue($act->provider->name);
        });
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cell('X' . $row, function ($cell) use ($act){
            $cell->setValue($act->customer->name);
        });
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $row++;

        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cell('E' . $row, function ($cell) use ($act){
            $cell->setValue('Генеральный директор');
        });
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cell('X' . $row, function ($cell) use ($act){
            $cell->setValue('Генеральный директор');
        });
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $row++;

        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cell('E' . $row, function ($cell) use ($act){
            $cell->setValue($act->provider->direktor);
        });
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cell('X' . $row, function ($cell) use ($act){
            $cell->setValue($act->customer->direktor);
        });
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setFontWeight('bold');
            $cells->setFontSize(9);
            $cells->setAlignment('center');
        });

        $row++;


        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setBorder('none', 'none', 'thin', 'none');
        });

        $row++;

        $sheet->mergeCells('E' . $row . ':P' . $row);
        $sheet->cell('E' . $row, function ($cell){
            $cell->setValue('подпись');
        });
        $sheet->cells('E' . $row . ':P' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });

        $sheet->mergeCells('X' . $row . ':AI' . $row);
        $sheet->cell('X' . $row, function ($cell){
            $cell->setValue('подпись');
        });
        $sheet->cells('X' . $row . ':AI' . $row, function ($cells) {
            $cells->setFontSize(8);
            $cells->setAlignment('center');
        });





//        $sheet->mergeCells('H' . $row . ':P' . $row);
//        $sheet->cell('H' . $row, function ($cell){
//            $cell->setValue('Генеральный директор');
//        });
//        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
//            $cells->setFontWeight('bold');
//            $cells->setFontSize(9);
//            $cells->setAlignment('center');
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $sheet->mergeCells('R' . $row . ':AA' . $row);
//        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $sheet->mergeCells('AC' . $row . ':AL' . $row);
//        $sheet->cell('AC' . $row, function ($cell) use ($act){
//            $cell->setValue($act->provider->direktor);
//        });
//        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
//            $cells->setFontWeight('bold');
//            $cells->setFontSize(10);
//            $cells->setAlignment('center');
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $row++;
//
//        $sheet->mergeCells('H' . $row . ':P' . $row);
//        $sheet->cell('H' . $row, function ($cell){
//            $cell->setValue('должность');
//        });
//        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        $sheet->mergeCells('R' . $row . ':AA' . $row);
//        $sheet->cell('R' . $row, function ($cell){
//            $cell->setValue('подпись');
//        });
//        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        $sheet->mergeCells('AC' . $row . ':AL' . $row);
//        $sheet->cell('AC' . $row, function ($cell){
//            $cell->setValue('расшифровка подписи');
//        });
//        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        $row++;
//        $row++;
//
//        $sheet->mergeCells('B' . $row . ':F' . $row);
//        $sheet->cell('B' . $row, function ($cell){
//            $cell->setValue('Ответственный');
//        });
//        $sheet->cells('B' . $row . ':F' . $row, function ($cells) {
//            $cells->setFontWeight('bold');
//            $cells->setFontSize(9);
//        });
//
//        $sheet->mergeCells('H' . $row . ':P' . $row);
//        $sheet->cell('H' . $row, function ($cell){
//            $cell->setValue('Менеджер');
//        });
//        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
//            $cells->setFontWeight('bold');
//            $cells->setFontSize(9);
//            $cells->setAlignment('center');
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $sheet->mergeCells('R' . $row . ':AA' . $row);
//        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $sheet->mergeCells('AC' . $row . ':AL' . $row);
//        $sheet->cell('AC' . $row, function ($cell) use ($act){
//            $cell->setValue($act->provider->manager);
//        });
//        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
//            $cells->setFontWeight('bold');
//            $cells->setFontSize(10);
//            $cells->setAlignment('center');
//            $cells->setBorder('none', 'none', 'thin', 'none');
//        });
//
//        $row++;
//
//        $sheet->mergeCells('H' . $row . ':P' . $row);
//        $sheet->cell('H' . $row, function ($cell){
//            $cell->setValue('должность');
//        });
//        $sheet->cells('H' . $row . ':P' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        $sheet->mergeCells('R' . $row . ':AA' . $row);
//        $sheet->cell('R' . $row, function ($cell){
//            $cell->setValue('подпись');
//        });
//        $sheet->cells('R' . $row . ':AA' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        $sheet->mergeCells('AC' . $row . ':AL' . $row);
//        $sheet->cell('AC' . $row, function ($cell){
//            $cell->setValue('расшифровка подписи');
//        });
//        $sheet->cells('AC' . $row . ':AL' . $row, function ($cells) {
//            $cells->setFontSize(8);
//            $cells->setAlignment('center');
//        });
//
//        //for pdf
//        $sheet->cells('A1:A' . $row, function ($cells) {
//            $cells->setBorder('none', 'none', 'none', 'none');
//        });

        return $row;
    }
}