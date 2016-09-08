<?php
namespace App\Layers;

use Framework\Request\Types\HttpRequest;
use Framework\View\Modificator;
use Framework\View\Url;
use Framework\View\View;
use App\Models\Product\Category;

class LayerView extends View
{
    /**
     * @param $path
     * @param array $data
     */
    public function __construct($path, array $data = [])
    {
        $data['categories'] = (new Category())->getQuery()->select()->execute()->all()->get();

        $main = [
            'text' => 'Главная',
            'link' => $this->getUrl()->route('main')
        ];
        if(isset($data['breadcrumbs'])){
            array_unshift($data['breadcrumbs'], $main);
        }else{
            $data['breadcrumbs'][] = $main;
        }

        $data['search'] = HttpRequest::get('q');

        $this->page = $this->setViewContent($path, $data)->getLayoutContent($data);
    }
}