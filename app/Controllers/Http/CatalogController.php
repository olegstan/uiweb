<?php
namespace App\Controllers\Http;

use App\Layers\LayerHttpController;
use App\Models\Product\Category;
use App\Models\Product\Product;
use Framework\Request\Types\HttpRequest;
use Framework\Response\Types\HtmlResponse;
use App\Layers\LayerView as View;

class CatalogController extends LayerHttpController
{
    public function catalog(HttpRequest $request)
    {
        return new HtmlResponse(new View('catalog/catalog.php', [
            'products' => (new Product())
                ->getQuery()
                ->with(['images'])
                ->select(['p.id', 'p.price', 'p.ipc2u_price', 'p.insat_price', 'p.url AS product_url', 'p.name', 'pc.url AS category_url'])->from('products AS p')->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
                ->where('p.is_active = 1')
                ->limit(0, 32)
                ->execute()
                ->all()
                ->get()
        ]));
    }

    public function category(HttpRequest $request)
    {
        /**
         * @var Category $category
         */
        $category = (new Category())
            ->getQuery()
            ->select()
            ->where('url = :url', [':url' => $request->getRoute('category_url')])
            ->execute()
            ->one()
            ->get();

        if($category){
            return new HtmlResponse(new View('catalog/category.php', [
                'category' => $category,
                'title' => $category->title,
                'meta_keywords' => $category->meta_keywords,
                'meta_description' => $category->meta_description,
                'products' => (new Product())
                    ->getQuery()
                    ->with(['images'])
                    ->select(['p.id', 'p.price', 'p.ipc2u_price', 'p.insat_price', 'p.url AS product_url', 'p.name', 'pc.url AS category_url'])->from('products AS p')->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
                    ->where('p.category_id = :category_id AND p.is_active = 1', [':category_id' => $category->id])
                    ->limit(0, 32)
                    ->execute()
                    ->all()
                    ->get()
            ]));
        }else{
            return $this->{'404'}();
        }
    }

    public function product(HttpRequest $request)
    {
        /**
         * @var Product $product
         */
        $product = (new Product())
            ->getQuery()
            ->with(['images', 'tags'])
            ->select([
                'p.id',
                'p.url AS product_url',
                'p.name',
                'p.title',
                'p.meta_keywords',
                'p.meta_description',
                'pc.url AS category_url'
            ])
            ->from('products AS p')
            ->where('p.url = :product_url AND pc.url = :category_url AND p.is_active = 1', [
                ':product_url' => $request->getRoute('product_url'),
                ':category_url' => $request->getRoute('category_url')
            ])
            ->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
            ->execute()
            ->one()
            ->get();

        if($product){
            return new HtmlResponse(new View('catalog/product.php', [
                'product' => $product,
                'title' => $product->title,
                'meta_keywords' => $product->meta_keywords,
                'meta_description' => $product->meta_description,
            ]));
        }else{
            return $this->{'404'}();
        }
    }

    public function search(HttpRequest $request)
    {
        $products = [];
        if(!empty($request->get('q'))){
            $products = (new Product())
                ->getQuery()
                ->with(['images'])
                ->select(['p.id', 'p.price', 'p.ipc2u_price', 'p.insat_price', 'p.url AS product_url', 'p.name', 'pc.url AS category_url'])->from('products AS p')->leftJoin('products_categories AS pc', 'p.category_id = pc.id')
                ->where('p.name LIKE :name AND p.is_active = 1', [':name' => '%' . $request->get('q') . '%'])
                ->limit(0, 32)
                ->execute()
                ->all()
                ->get();
        }

        return new HtmlResponse(new View('catalog/search.php', [
            'search' => $request->get('q'),
            'products' => $products
        ]));
    }
}