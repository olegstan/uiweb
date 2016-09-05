<?php
namespace app\system\controllers;

use app\layer\LayerController;
use app\models\attachment\Attachment;
use app\models\badge\Badge;
use app\models\Brand;
use app\models\category\Category;
use app\layer\LayerAdminController;
use app\models\Currency;
use app\models\image\Image;
use app\models\product\Product;
use app\models\product\ProductCategory;
use app\models\tag\Tag;
use app\models\tag\TagGroup;
use app\models\Variant;
use core\helper\Response;

class ProductController extends LayerAdminController
{
    public function index()
    {
        return Response::html($this->render(SYSTEM_TPL . '/system/html/product/index.tpl'));
    }

    public function create()
    {
        $this->getCore()->asset->addFooterJS('/libraries/ckeditor/ckeditor.js');

        // Теги товара
        $all_tags_groups = (new TagGroup())
            ->query()
            ->select()
            ->where('is_enabled = 1 AND is_auto = 0')
            ->order('position')
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('all_tags_groups', $all_tags_groups);

        $all_brands = (new Brand())
            ->query()
            ->select()
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('all_brands', $all_brands);

        $all_categories = (new Category())
            ->query()
            ->with(['image'])
            ->select()
            ->execute()
            ->all()
            ->toTree('parent_id')
            ->getResult();

        $this->design->assign('all_categories', $all_categories);


        //Рейтинг

//        //Сопутствующие товары
//        $related_products = $this->products->get_related_products(array('product_id'=>$product->id, 'product_type'=>0));
//        if ($related_products)
//        {
//            foreach($related_products as $index=>$related)
//            {
//                $related_products[$index] = $this->products->get_product($related->related_id);
//                $related_products[$index]->variants = $this->variants->get_variants(array('product_id'=>$related->related_id));
//                $related_products[$index]->variant = @reset($related_products[$index]->variants);
//                $related_products[$index]->in_stock = false;
//                $related_products[$index]->in_order = false;
//                foreach($related_products[$index]->variants as $rv)
//                    if ($rv->stock > 0)
//                        $related_products[$index]->in_stock = true;
//                    else
//                        if ($rv->stock < 0)
//                            $related_products[$index]->in_order = true;
//                $related_products[$index]->images = $this->image->get_images('products', $related->related_id);
//                if (isset($related_products[$index]->images[0]))
//                    $related_products[$index]->image = $related_products[$index]->images[0];
//                $related_products[$index]->attachments = $this->attachments->get_attachments('products', $related->related_id);
//                $related_products[$index]->badges = $this->badges->get_product_badges($related->related_id);
//            }
//            $this->design->assign('related_products', $related_products);
//        }
//
//        //Аналогичные товары
//        //$analogs_products = $this->products->get_related_products(array('product_id'=>$product->id, 'product_type'=>3));
//        $analogs_products = $this->products->get_analogs_by_product_id($product->id);
//        if ($analogs_products)
//        {
//            $this->design->assign('analogs_group_id', $analogs_products[0]->group_id);
//            foreach($analogs_products as $index=>$analog)
//            {
//                $analogs_products[$index] = $this->products->get_product($analog->product_id);
//                $analogs_products[$index]->variants = $this->variants->get_variants(array('product_id'=>$analog->product_id));
//                $analogs_products[$index]->variant = @reset($analogs_products[$index]->variants);
//                $analogs_products[$index]->in_stock = false;
//                $analogs_products[$index]->in_order = false;
//                foreach($analogs_products[$index]->variants as $rv)
//                    if ($rv->stock > 0)
//                        $analogs_products[$index]->in_stock = true;
//                    else
//                        if ($rv->stock < 0)
//                            $analogs_products[$index]->in_order = true;
//                $analogs_products[$index]->images = $this->image->get_images('products', $analog->product_id);
//                if (isset($analogs_products[$index]->images[0]))
//                    $analogs_products[$index]->image = $analogs_products[$index]->images[0];
//                $analogs_products[$index]->attachments = $this->attachments->get_attachments('products', $analog->product_id);
//                $analogs_products[$index]->badges = $this->badges->get_product_badges($analog->product_id);
//            }
//            $this->design->assign('analogs_products', $analogs_products);
//        }




        //$this->design->assign('product_categories', $product_categories);
        return Response::html($this->render(SYSTEM_TPL . '/system/html/product/product.tpl'));
    }

    public function edit($id)
    {
        $this->getCore()->asset->addFooterJS('/libraries/ckeditor/ckeditor.js');
        $this->getCore()->asset->addFooterJS('/libraries/ckeditor/config.js');

        $all_currencies = (new Currency())
            ->query()
            ->select()
            ->order('position')
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('all_currencies', $all_currencies);

        $product = (new Product())
            ->query()
            ->with([
                'variants',
                'tags',
                'rating',
                'related',
                'analogs'
            ])
            ->select()
            ->where('id = :id', [':id' => $id])
            ->limit()
            ->execute()
            ->one(['is_visible' => 1])
            ->getResult();


        $this->design->assign('product', $product);

        $all_tags_groups = (new TagGroup())
            ->query()
            ->select()
            ->where('is_enabled = 1 AND is_auto = 0')
            ->order('position')
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('all_tags_groups', $all_tags_groups);

        $all_categories = (new Category())
            ->query()
            ->with(['image'])
            ->select()
            ->execute()
            ->all()
            ->toTree('parent_id')
            ->getResult();

        $this->design->assign('all_categories', $all_categories);

        // категории товара
        $product_categories = (new Product())
            ->query()
            ->select(['category_id'])
            ->leftJoin('mc_products_categories', 'mc_products.id = mc_products_categories.product_id')
            ->where('mc_products.id = :product_id', [':product_id' => $product->id])
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('product_categories', $product_categories);


//        if (!empty($product->modificators))
//            $product->modificators = explode(',', $product->modificators);
//        else
//            $product->modificators = array();
//        if (!empty($product->modificators_groups) && !is_array($product->modificators_groups))
//            $product->modificators_groups = explode(',', $product->modificators_groups);
//        else
//            $product->modificators_groups = array();

        // Бейджи товара
        $product_badges = (new Badge())->get_product_badges($product->id);
        $this->design->assign('product_badges', $product_badges);

        // Аттачи товара
        $attachments = (new Attachment())->get_attachments('products', $product->id);
        $this->design->assign('attachments', $attachments);

        // Изображения товара
        $images = (new Image())
            ->query()
            ->select()
            ->where('object_id = :object_id', [':object_id' => $product->id])
            ->execute()
            ->all(['folder' => 'products'])
            ->getResult();
        $this->design->assign('images', $images);

        //Теги товара
        $product_tags_positions = [];
        $product_tags_groups = [];
        $product_tags = [];

        //Группы тегов, которые входят в набор групп тегов
        if ($product_categories){
            foreach($product_categories as $pc){
                $categories_array = array();
                $tmp_category = (new Category())->get_category($pc->category_id);
                while ($tmp_category){
                    $categories_array[] = $tmp_category->id;
                    $tmp_category = (new Category())->get_category($tmp_category->parent_id);
                }

                $set_tag_groups = (new Tag())->get_tags_set_tags(array('category_id'=>$categories_array));

                foreach($set_tag_groups as $gr){
                    if (!array_key_exists($gr->group_id, $product_tags_groups)){
                        $product_tags_groups[$gr->group_id] = array();
                        $product_tags_positions[] = $gr->group_id;
                    }
                }
            }
        }

        // Группы тегов товара
        $tags_groups = (new TagGroup())
            ->query()
            ->with(['tags'])
            ->select()
            ->execute()
            ->all(null, 'id')
            ->getResult();


        foreach($product->tags as $tag)
        {
            if (!array_key_exists($tag->group_id, $product_tags_groups))
            {
                $product_tags_groups[$tag->group_id] = array();
                $product_tags_positions[] = $tag->group_id;
            }
            $tags_groups[$tag->group_id]->tags[$tag->id]->selected = true;
            $product_tags[$tag->group_id][$tag->id] = $tag;
        }

        $this->design->assign('tags_groups', $tags_groups);
        $this->design->assign('product_tags', $product_tags);
        $this->design->assign('product_tags_groups', $product_tags_groups);
        $this->design->assign('product_tags_positions', $product_tags_positions);


        // выбранный таб
        $tag = $this->getCore()->request->get('tab');
        $this->design->assign('tab', $tag);

        // бренды
        $all_brands = (new Brand())
            ->query()
            ->select()
            ->execute()
            ->all()
            ->getResult();

        $this->design->assign('all_brands', $all_brands);

        return Response::html($this->render(SYSTEM_TPL . '/system/html/product/product.tpl'));
    }

    public function save($id = null)
    {
        /**
         * выделить в функцию определяющую действие
         */
        //buttons
        $action = 'cancel';
        $save = $this->getCore()->request->post('save');
        $save_and_close = $this->getCore()->request->post('save_and_close');
        $save_and_create = $this->getCore()->request->post('save_and_create');
        $cancel = $this->getCore()->request->post('cancel');
        $delete = $this->getCore()->request->post('delete');


        if(isset($save)){
            $action = 'save';
        }else if(isset($save_and_close)){
            $action = 'save_and_close';
        }else if(isset($save_and_create)){
            $action = 'save_and_create';
        }else if(isset($cancel)){
            $action = 'cancel';
        }else if(isset($delete)){
            $action = 'delete';
        }

        if($action === 'delete'){
            return $this->getCore()->redirect->redirect('/admin/product/', 302);
        }

        if($action === 'cancel'){
            return $this->getCore()->redirect->redirect('/admin/product/edit/' . $id, 302);
        }

        if($id){
            $product = (new Product())
                ->query()
                ->with(['variants', 'tags'])
                ->select()
                ->where('id = :id', [':id' => $id])
                ->limit()
                ->execute()
                ->one()
                ->getResult();
        }else{
            $product = new Product();
        }

        $product->removeAutoTags();
        $product->removeGuard();

        $product->name = $this->getCore()->request->post('name');
        $product->brand_id = $this->getCore()->request->post('brand_id');
        $product->meta_title = $this->getCore()->request->post('meta_title');
        $product->meta_keywords = $this->getCore()->request->post('meta_keywords');
        $product->meta_description = $this->getCore()->request->post('meta_description');

        $product->annotation = $this->getCore()->request->post('annotation');
        $product->annotation2 = $this->getCore()->request->post('annotation2');
        $product->body = $this->getCore()->request->post('body');

        $product->url = $this->getCore()->request->post('url');
        $product->is_visible = $this->getCore()->request->post('is_visible');

        $product->currency_id = $this->getCore()->request->post('currency_id');

        if($this->getCore()->request->has('add_flag1')){
            $product->add_flag1 = $this->getCore()->request->post('add_flag1');
        }
        if($this->getCore()->request->has('add_flag2')){
            $product->add_flag2 = $this->getCore()->request->post('add_flag2');
        }
        if($this->getCore()->request->has('add_flag3')){
            $product->add_flag3 = $this->getCore()->request->post('add_flag3');
        }

        if($this->getCore()->request->has('add_field1')){
            $product->add_field1 = $this->getCore()->request->post('add_field1');
        }
        if($this->getCore()->request->has('add_field2')){
            $product->add_field2 = $this->getCore()->request->post('add_field3');
        }
        if($this->getCore()->request->has('add_field3')){
            $product->add_field3 = $this->getCore()->request->post('add_field3');
        }

        $varaints = $this->getCore()->request->post('variants');
        $tags = $this->getCore()->request->post('tags');
        $categories_ids = $this->getCore()->request->post('categories_ids');

        if($id){
            $product->update();
        }else{
            $product->insert();
        }

        $product->saveVariants($varaints);
        $product->saveTags($tags);
        $product->saveAutoTags();
        $product->saveCategories($categories_ids);

        switch($action){
            case 'save':
                return $this->getCore()->redirect->redirect('/admin/product/edit/' . $product->id, 302);
            case 'save_and_close':
                return $this->getCore()->redirect->redirect('/admin/product/', 302);
            case 'save_and_create':
                return $this->getCore()->redirect->redirect('/admin/product/edit', 302);
        }
    }
}