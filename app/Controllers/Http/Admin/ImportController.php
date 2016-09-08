<?php
namespace App\Controllers\Http\Admin;

use App\Layers\LayerAdminController;
use App\Models\Product\Brand;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Models\System\Currency;
use Framework\Text\Translit;
use PHPExcel_IOFactory;
use PHPExcel_Cell;

class ImportController extends LayerAdminController
{
    public function importFile($path)
    {
        $excel = PHPExcel_IOFactory::load($path);

        foreach ($excel->getWorksheetIterator() as $worksheet) {
            $last_row = $worksheet->getHighestRow();
            $last_col = $worksheet->getHighestColumn(); // e.g 'F'
            //$last_column_index = PHPExcel_Cell::columnIndexFromString($last_col);

            for ($row = 1; $row <= $last_row; ++$row) {
                $cell = $worksheet->getCellByColumnAndRow(0, $row);
                $val = $cell->getValue();

                if(!isset($val)){
                    continue;
                }


                $cell = $worksheet->getCellByColumnAndRow(1, $row);
                $val = $cell->getValue();

                if(!isset($val)){
                    continue;
                }

                $cell = $worksheet->getCellByColumnAndRow(2, $row);
                $val = $cell->getValue();

                if(!isset($val)){
                    $cell = $worksheet->getCellByColumnAndRow(1, $row);
                    $val = trim($cell->getValue());

                    $category = (new Category())->getQuery()
                        ->select()
                        ->where('url = :url', [':url' => Translit::make($val, [' ' => '-', '+' => '-plus'])])
                        ->limit()
                        ->execute()
                        ->one()
                        ->getResult();

                    if(!$category){
                        $category_id = (new Category([
                            'name' => $val,
                            'url' => Translit::make($val, [' ' => '-', '+' => '-plus']),
                            'title' => $val,
                            'meta_keywords' => $val,
                            'meta_description' => $val,
                            'created_at' => date('Y.m.d H:i:s', time())
                        ]))->insert();
                    }else{
                        $category_id = $category->id;
                    }
                    continue;
                }


                $cell = $worksheet->getCellByColumnAndRow(0, $row);
                $code = trim($cell->getValue());

                $cell = $worksheet->getCellByColumnAndRow(1, $row);
                $name = trim($cell->getValue());

                if($name === 'Название'){
                    continue;
                }

                $cell = $worksheet->getCellByColumnAndRow(2, $row);
                $description = $cell->getValue();

                $cell = $worksheet->getCellByColumnAndRow(3, $row);
                $brand = $cell->getValue();

                $cell = $worksheet->getCellByColumnAndRow(4, $row);
                $currency = $cell->getValue();

                $cell = $worksheet->getCellByColumnAndRow(5, $row);
                $price = $cell->getValue();

                $cell = $worksheet->getCellByColumnAndRow(6, $row);
                $buying_price = $cell->getValue();

                //0 - code
                //1 - name
                //2 - preview description
                //3 - brand
                //4 - currency
                //5 - price
                //6 - buying_price
                //

                $product = (new Product())->getQuery()
                    ->select()
                    ->where('url = :url', [':url' => Translit::make($name, [' ' => '-', '+' => '-plus'])])
                    ->limit()
                    ->execute()
                    ->one()
                    ->getResult();

                if(!$product){
                    (new Product([
                        'name' => $name,
                        'code' => $code,
                        'url' => Translit::make($name, [' ' => '-', '+' => '-plus']),
                        'preview' => $description,
                        'description' => $description,
                        'category_id' => $category_id,
                        'brand_id' => (new Brand())->getByAlias(strtolower($brand))->id,
                        'currency_id' => (new Currency())->getByCode($currency)->id,
                        'title' => $name,
                        'meta_keywords' => $name,
                        'meta_description' => $name,
                        'price' => $price,
                        'buying_price' => $buying_price,
                    ]))->insert();
                    echo 'product inserted ' . $name . ' ' . Translit::make($name, [' ' => '-', '+' => '-plus']) . '<br>';
                }else{
                    echo 'product exists ' . $name . ' ' . Translit::make($name, [' ' => '-', '+' => '-plus']) . '<br>';
                }

//
//                for ($col = 0; $col < $highestColumnIndex; ++$col) {
//                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
//                    $val = $cell->getValue();
//                    echo '<pre>';
//                    var_dump($row . ' row');
//                    var_dump($col . ' col');
//                    echo '</pre>';
//
//                    (new Product([
//                        'name' =>
//                    ]))->insert();;
//                    if ($row === 1) {
//                        echo '<pre>';
//                        var_dump($val);
//                        echo '</pre>';
//                    } else {
//                        echo '<pre>';
//                        var_dump($val);
//                        echo '</pre>';
//                    }
////                        echo '<td>' . $val . '</td>';
//                }
            }
        }
    }

    public function getImport()
    {
        $files = [
            '/resources/files/MOXA.xls',
            '/resources/files/ADVANTECH.xls',
            '/resources/files/ADlink.xls',
            '/resources/files/ICP DAS.xls',
            '/resources/files/NEXCOM.xls',
            '/resources/files/IEI.xls'
        ];

        foreach ($files as $file) {
            $this->importFile(ABS . $file);
        }
    }

//        foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
//            $worksheetTitle     = $worksheet->getTitle();
//            $highestRow         = $worksheet->getHighestRow(); // e.g. 10
//            $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
//            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
//            $nrColumns = ord($highestColumn) - 64;
//            echo '<br>Data: <table width="100%" cellpadding="3" cellspacing="0"><tr>';
//            for ($row = 1; $row <= $highestRow; ++ $row) {
//
//                echo '<tr>';
//                for ($col = 0; $col < $highestColumnIndex; ++ $col) {
//                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
//                    $val = $cell->getValue();
//                    if($row === 1)
//                        echo '<td style="background:#000; color:#fff;">' . $val . '</td>';
//                    else
//                        echo '<td>' . $val . '</td>';
//                }
//                echo '</tr>';
//            }
//            echo '</table>';
//        }
//    }

    public function postImport()
    {
        echo 2;
    }
}