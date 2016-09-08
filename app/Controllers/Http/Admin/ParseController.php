<?php
namespace App\Controllers\Http\Admin;

use App\Layers\LayerAdminController;
use App\Models\Product\Image;
use App\Models\Product\Product;
use App\Models\Tag\Group;
use App\Models\Tag\Tag;
use App\Models\Tag\Value;
use Framework\Curl\Curl;
use Framework\FileSystem\Folder;
use Framework\Response\Types\HtmlResponse;
use Framework\Text\Charset;
use Framework\Text\Translit;
use \DOMDocument;
use \DomXPath;
use \DOMNodeList;
use \DOMNamedNodeMap;
use \DOMElement;
use \DOMAttr;
use \DOMNode;

class ParseController extends LayerAdminController
{
    public function getParseIpc2uImages()
    {
        die();
        //ipc2u
        libxml_use_internal_errors(true);
        $produtcs = (new Product())->getQuery()->select()->execute()->all()->get();

        foreach ($produtcs as $product) {
            $curl = new Curl();
            $response = $curl->get('http://ipc2u.ru/catalog/' . $product->url);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML($response->getContent());

            $finder = new DomXPath($dom);
            $nodes = $finder->query('//div[@class=\'box-slider\']//img');
            $images = [];
            if($nodes->length > 0){
                /**
                 * @var DOMNode $node
                 */
                foreach ($nodes as $node) {
                    /**
                     * @var DOMAttr $attribute
                     */
                    foreach ($node->attributes as $attribute) {
                        if($attribute->name === 'src'){
                            $images[$attribute->nodeValue] = $attribute->nodeValue;
                        }
                    }
                }
            }else{
                echo 'no product ' . $product->name . '<br>';
                continue;
            }


            Folder::create(ABS . '/public/assets/img/products/' . $product->url . '/');
            $i = 0;
            foreach($images as $image){
                $i++;
                (new Image([
                    'product_id' => $product->id,
                    'path' => $product->url . '/' . $product->url . '-' . $i . '.jpg'
                ]))->insert();
                copy($image, ABS . '/public/assets/img/products/' . $product->url . '/' . $product->url . '-' . $i . '.jpg');
            }
        }
        return new HtmlResponse('success');
    }

    public function getParseIpc2uProducts($update_price = false)
    {
        libxml_use_internal_errors(true);
        //5961
        $produtcs = (new Product())->getQuery()->select()->limit(5961, 9999999)->execute()->all()->get();

        foreach ($produtcs as $product) {
            $curl = new Curl();
            $response = $curl->get('http://ipc2u.ru/catalog/' . $product->url);

            echo 'http://ipc2u.ru/catalog/' . $product->url;


            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML($response->getContent());

            $finder = new DomXPath($dom);
            $nodes = $finder->query('//div[@class=\'specification\']');
            if($nodes->length === 1){
                /**
                 * @var DOMNode $node
                 */
                $curl = new Curl();
                $response = $curl->get('http://ipc2u.ru/catalog/' . $product->url);

                $dom = new DOMDocument('1.0', 'UTF-8');
                $dom->loadHTML($response->getContent());

                $finder = new DomXPath($dom);
                $nodes = $finder->query('//div[@class=\'specification\']//*');

                if($nodes->length > 0){

                    foreach($nodes as $k => $node){
                        /**
                         * @var DOMElement $node
                         */
                        //detect group
                        if(isset($node->attributes[0]->value) && $node->attributes[0]->value === 'sTitle'){
                            $group = (new Group())
                                ->getQuery()
                                ->select()
                                ->where('name = :name', [':name' => $node->nodeValue])
                                ->limit()
                                ->execute()
                                ->one()
                                ->get();

                            if(!$group){
                                $group = new Group();
                                $group->name = $node->nodeValue;
                                $group->save();
                            }
                        }

                        if($node->getElementsByTagName('dt')->length === 1){
                            $tag_node = str_replace([':'], '', trim($node->getElementsByTagName('dt')[0]->nodeValue));

                            $tag = (new Tag())
                                ->getQuery()
                                ->select()
                                ->where('name = :name', [':name' => $tag_node])
                                ->limit()
                                ->execute()
                                ->one()
                                ->get();

                            if(!$tag){
                                $tag = new Tag();
                                $tag->name = $tag_node;
                                $tag->save();
                            }
                        }

                        if($node->getElementsByTagName('dd')->length === 1){
                            $value_node = trim($node->getElementsByTagName('dd')[0]->nodeValue);

                            $value = (new Value())
                                ->getQuery()
                                ->select()
                                ->where('value = :value', [':value' => $value_node])
                                ->limit()
                                ->execute()
                                ->one()
                                ->get();

                            if(!$value){
                                $value = new Value();
                                $value->value = $value_node;
                                $value->save();
                            }


                            if(isset($group) && isset($tag) && isset($value) && !empty($group) && !empty($tag) && !empty($value)){
                                (new \App\Models\Product\Tag([
                                    'product_id' => $product->id,
                                    'group_id' => $group->id,
                                    'tag_id' => $tag->id,
                                    'value_id' => $value->id
                                ]))->save();
                            }
                        }
                    }
                }
            }else{
                echo 'no product ' . $product->name . '<br>';
                continue;
            }
        }
        return new HtmlResponse('success');
    }


    public function getParseIpc2uPrices($update_price = false)
    {
        libxml_use_internal_errors(true);
        $produtcs = (new Product())->getQuery()->select()->execute()->all()->get();
        foreach ($produtcs as $product) {
            $curl = new Curl();
            $response = $curl->get('http://ipc2u.ru/catalog/' . $product->url);

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTML($response->getContent());

            $finder = new DomXPath($dom);
            $nodes = $finder->query('//div[@class=\'box-card\']');
            if($nodes->length === 1){
                /**
                 * @var DOMNode $node
                 */
                $node = $nodes[0];
                /**
                 * @var DOMAttr $attribute
                 */
                foreach ($node->attributes as $attribute) {
                    if($attribute->name === 'data-ng-init'){
                        $lines = explode("\n", $attribute->value);
                        if($lines){
                            foreach ($lines as &$line) {
                                $line = mb_ereg_replace('( )|(<i>)|(</i>)|(<span>)|(</span>)|(\,)|(\.)|(\")', '', trim($line));
                                $line = strtr($line, ['у' => '', 'е' => '']);

//                                if(preg_match('#^PRINT_DISCOUNT_VALUE:([0-9]{1,10})([0-9]{2})(уе)#', $line, $matches)){
                                if(preg_match('#^PRINT_DISCOUNT_VALUE:([0-9]{1,15})#', $line, $matches)){
                                    if(isset($matches[1])){
                                        $integer = substr($matches[1], 0, count($matches[1]) - 3);
                                        $decimal = substr($matches[1], -2);

                                        $product->ipc2u_price = floatval($integer . '.' .  $decimal);
                                        $product->save();
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($nodes as $node) {
                    /**
                     * @var DOMAttr $attribute
                     */
                    foreach ($node->attributes as $attribute) {
                        if($attribute->name === 'src'){
                            $images[$attribute->nodeValue] = $attribute->nodeValue;
                        }
                    }
                }
            }else{
                echo 'no product ' . $product->name . '<br>';
                continue;
            }
        }
        return new HtmlResponse('success');
    }

    public function getParseInsatPrices($update_price = false)
    {
        //insat
        libxml_use_internal_errors(true);
        for($i = 1; $i <= 1000; $i++){
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadHTMLFile('http://www.insat.ru/prices/info.php?pid=' . $i);

            $finder = new DomXPath($dom);

            $nodes = $finder->query('//td[@class=\'largetext\']');
            if($nodes->length === 1){
                $name = str_replace('  ', ' ', trim($nodes[0]->textContent));
            }else{
                echo 'no product ' . $i . '<br>';
                continue;
            }

            $product = (new Product())->getQuery()
                ->select()
                ->where('url = :url', [':url' => Translit::make($name, [' ' => '-', '+' => '-plus'])])
                ->execute()
                ->one()
                ->getResult();

            if($product){
                echo 'finded ' . $product->name . '<br>';
            }else{
                echo 'not founded ' . $name . '<br>';
                continue;
            }

            $nodes = $finder->query('//font[@class=\'opth\']');
            if($nodes->length > 0){
                foreach ($nodes as $node) {
                    /**
                     * @var DOMElement $node
                     */

                    $size = (int)$node->getAttribute('size');

                    if($size == 4){
                        $price = (float)trim(str_replace(['$', '  '], ' ', trim($node->textContent)));

                        $product->insat_price = $price;
                        $product->insat_id = $i;
                        $product->save();
                    }
                }
            }else{
                echo 'not price ' . $product->name . '<br>';
                continue;
            }
        }
        return new HtmlResponse('success');
    }
}