<?php
namespace App\Models\Product;

use App\Layers\LayerDatabaseModel;
use Framework\Model\Collection;

/**
 * Class Product
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $category_url
 * @property string $product_url
 * @property string $preview
 * @property string $description
 * @property int $currency_id
 * @property float $price
 * @property float $insat_price
 * @property float $ipc2u_price
 * @property int $brand_id
 * @property int $category_id
 * @property string $title
 * @property string $meta_keywords
 * @property string $meta_description
 * @property int $position
 * @property string $created_at
 * @property string $modified_at
 * @property int $views
 * @property int $buys
 * @property int $ctr
 * @property bool $is_active
 * @property array $images
 * @property array $tags
 *
 * @package App\Models\Product
 */
class Product extends LayerDatabaseModel
{
    protected $table = 'products';

    //связанные товары
    /**
     * @var array
     */
    public $related = [];
    /**
     * @var array
     */
    public $analogs = [];
    /**
     * @var Image
     */
    public $image;
    /**
     * @var array
     */
    public $images = [];
    /**
     * @var float
     */
    public $rating;
    /**
     * @var array
     */
    public $tags = [];
    /**
     * @var array
     */
    public $tags_groups = [];
    /**
     * @var string
     */
    public $url;
    /**
     * @var int
     */
    public $views;
    /**
     * @var int
     */
    public $buys;
    /**
     * @var int
     */
    public $ctr;

    protected $fillable = [
        'name',
        'url',
        'preview',
        'description',
        'code',
        'currency_id',
        'price',
        'buying_price',
        'ipc2u_price',
        'insat_price',
        'brand_id',
        'category_id',
        'title',
        'meta_keywords',
        'meta_description',
        'position',
        'created_at',
        'modified_at',
        'views',
        'buys',
        'ctr',
        'is_active',
    ];

    public function getPrice()
    {
        return $this->price;
    }

    public static function images(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                /**
                 * @var Product $product
                 */
                $product = $collection->get();

                if($product){
                    $images = (new Image())
                        ->getQuery()
                        ->select()
                        ->where('product_id = :product_id', [':product_id' => $product->id])
                        ->order('position')
                        ->execute()
                        ->all()
                        ->get();

                    if($images) {
                        foreach($images as $image){
                            if(isset($product)){
                                if(empty($product->images)){
                                    $product->image = $image;
                                }
                                $product->images[] = $image;
                            }
                        }
                    }

                    if(empty($product->images)){
                        $product->image = new Image(['path' => 'default.png']);
                    }
                }

                break;
            case 'all':
                $products = $collection->get();
                $products_ids = $collection->getField('id');
                $products_map = $collection->toMap()->getMap();

                if($products){
                    $images = (new Image())
                        ->getQuery()
                        ->select()
                        ->where('product_id IN (' . implode(',', $products_ids) . ')')
                        ->order('position')
                        ->execute()
                        ->all()
                        ->get();

                    if($images) {
                        foreach($images as $image){
                            if(isset($products_map[$image->product_id])){
                                if(empty($products_map[$image->product_id]->images)){
                                    $products_map[$image->product_id]->image = $image;
                                }
                                $products_map[$image->product_id]->images[] = $image;
                            }
                        }
                    }
                    foreach ($products as $product) {
                        if(empty($products_map[$product->id]->images)){
                            $products_map[$product->id]->image = new Image(['path' => 'default.png']);
                        }
                    }

                }
                break;
        }
    }

    public static function tags(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':
                $product = $collection->get();

                if($product){
                    $tags = (new Tag())
                        ->getQuery()
                        ->select([
                            'pt.id',
                            'pt.group_id',
                            'pt.tag_id',
                            'pt.value_id',
                            'tg.name AS group_name',
                            't.name AS tag_name',
                            'tv.value AS tag_value'
                        ])
                        ->from('products_tags AS pt')
                        ->leftJoin('tags_groups AS tg', 'pt.group_id = tg.id')
                        ->leftJoin('tags AS t', 'pt.tag_id = t.id')
                        ->leftJoin('tags_values AS tv', 'pt.value_id = tv.id')
                        ->where('pt.product_id = :product_id', [':product_id' => $product->id])
                        ->execute()
                        ->all()
                        ->get();

                    if($tags){
                        $product->tag_groups = [];
                        foreach($tags as $tag){
                            if(!isset($product->tags_groups[$tag->group_id])){
                                $product->tags_groups[$tag->group_id] = $tag;
                            }
                            $product->tags[$tag->group_id][$tag->tag_id] = ['name' => $tag->tag_name, 'value' => $tag->tag_value];




                            //                            if($tag->is_auto){
//                                $product->autotags[$tag->id] = $tag;
//                            }else{
//                                $product->tags[$tag->id] = $tag;
//                            }
                        }
                    }
                }
                break;
            case 'all':

                break;
        }
    }
}

//
//
//<?php
//namespace app\models\product;
//
//use app\layer\LayerModel;
//use app\models\Brand;
//use app\models\product\ProductCategory;
//use app\models\image\Image;
//use app\models\Rating;
//use app\models\Review;
//use app\models\tag\Tag;
//use app\models\tag\TagProduct;
//use app\models\Variant;
//use app\models\badge\Badge;
//use app\models\badge\BadgeProduct;
//use core\Collection;
//use core\helper\Pagination;
//
//class Product extends LayerModel
//{
//    protected $table = 'mc_products';
//
//    public static $module_id = 2;
//
//    public $meta_title;
//    public $meta_keywords;
//    public $meta_description;
//
//    public $image;
//    public $images = [];
//
//    public $variant;
//    public $variants = [];
//    public $variants_count = 0;
//
//    public $modificators = [];
//
//    public $badges = [];
//
//    public $autotags = [];
//    public $autotags_string = '';
//
//    //связанные товары
//    public $related = [];
//    public $analogs = [];
//
//    public $brand_id;
//    public $brand;
//
//    public $rating;
//
//    public $url;
//
//    public $views;
//    public $buy;
//    public $ctr;
//
//    public $url_options = [
//        'slash' => '/',
//        'htm' => '.htm',
//        'html' => '.html'
//    ];
//
//    public $guarded = [
//        'url'
//    ];
//
//    public function __construct()
//    {
//        $this->image = (new Image())->defaultImage();
//    }
//
//    public function countViews()
//    {
//        $this->views++;
//    }
//
//    public function countBuy()
//    {
//        $this->buy++;
//    }
//
//    public function calculateCtr()
//    {
//        $this->ctr = (($this->views ? $this->views : 1) / ($this->buy ? $this->buy : 1)) * 100;
//    }
//
//    public function removeGuard($field = null)
//    {
//        if($field){
//            unset($this->guarded[$field]);
//        }else{
//            $this->guarded = [];
//        }
//    }
//
//    public function filter()
//    {
//
//    }
//
//    public function afterSelect($rules = null)
//    {
//        $this->front_url = $this->getCore()->settings->prefix_product_url . $this->url . $this->getCore()->config['product_url_end'];
//        $this->admin_url = '/admin/product/edit/' . $this->id;
//
//        switch($rules){
//            case 'resize':
//                $this->image->res(50, 50);
//                break;
//        }
//
//        return $this;
//    }
//
//    public static function tags(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $tags = (new Tag())
//                        ->query()
//                        ->distinct()
//                        ->select([
//                            't.id',
//                            't.group_id',
//                            't.name',
//                            't.is_enabled',
//                            't.position',
//                            't.is_auto',
//                            't.is_popular',
//                            'tg.name as group_name',
//                            'tg.prefix',
//                            'tg.postfix',
//                            'tg.show_prefix_in_frontend_filter',
//                            'tg.show_in_product_list',
//                            'tg.export2yandex'
//                        ])
//                        ->from('mc_tags AS t')
//                        ->innerJoin('mc_tags_groups AS tg', 't.group_id = tg.id')
//                        ->innerJoin('mc_tags_products AS tp', 't.id = tp.tag_id')
//                        ->where('tp.product_id = :product_id', [':product_id' => $product->id])
//                        ->order('tg.position')
//                        ->order('tg.name')
//                        ->order('t.position')
//                        ->execute()
//                        ->all(null, 'id')
//                        ->getResult();
//
//                    if($tags){
//                        foreach($tags as $tag){
//                            if($tag->is_auto){
//                                $product->autotags[$tag->id] = $tag;
//                            }else{
//                                $product->tags[$tag->id] = $tag;
//                            }
//                        }
//                    }
//                }
//                break;
//            case 'all':
//
//                break;
//        }
//    }
//
//    public static function badges(Collection $collection, $rules = null)
//    {
//
//    }
//
//    public static function related(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $related = (new ProductRelated())
//                        ->query()
//                        ->select([
//                            'pr.product_id',
//                            'pr.related_id',
//                            'pr.position',
//                            'pr.product_type'
//                        ])
//                        ->from('mc_products_related AS pr')
//                        ->leftJoin('mc_products AS p', 'pr.related_id = p.id')
//                        ->where('product_type = 0 AND product_id != :product_id', [':product_id' => $product->id])
//                        ->order('position')
//                        ->execute()
//                        ->all(null, 'product_id')
//                        ->getField('product_id');
//
//                    if($related){
//                        $related_products = (new Product())
//                            ->query()
//                            ->with(['variants'])
//                            ->select()
//                            ->where('id IN (' . implode(',', $related) . ')')
//                            ->execute()
//                            ->all(null, 'id')
//                            ->getResult();
//
//                        if($related_products){
//                            $product->related = $related_products;
//                        }
//
//                    }
//                }
//                break;
//            case 'all':
//
//                break;
//        }
//    }
//
//    public static function analogs(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $analogs = (new ProductRelated())
//                        ->query()
//                        ->select([
//                            'pr.product_id',
//                            'pr.related_id',
//                            'pr.position',
//                            'pr.product_type'
//                        ])
//                        ->from('mc_products_related AS pr')
//                        ->leftJoin('mc_products AS p', 'pr.related_id = p.id')
//                        ->where('product_type = 3 AND product_id != :product_id', [':product_id' => $product->id])
//                        ->order('position')
//                        ->execute()
//                        ->all(null, 'product_id')
//                        ->getField('product_id');
//
//                    if($analogs){
//                        $analogs_products = (new Product())
//                            ->query()
//                            ->select()
//                            ->where('id IN (' . implode(',', $analogs) . ')')
//                            ->execute()
//                            ->all(null, 'id')
//                            ->getResult();
//
//                        if($analogs_products){
//                            $product->analogs = $analogs_products;
//                        }
//                    }
//                }
//                break;
//            case 'all':
//
//                break;
//        }
//    }
//
//    public static function rating(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $rating = (new Rating())
//                        ->query()
//                        ->select([
//                            '(ROUND(AVG(rating) * 2) / 2) AS avg_rating',
//                            'AVG(rating) AS avg_rating_real',
//                            'COUNT(id) AS rating_count'
//                        ])
//                        ->where('product_id = :product_id', [':product_id' => $product->id])
//                        ->group('product_id')
//                        ->execute()
//                        ->one(null, 'id')
//                        ->getResult();
//                    if($rating){
//                        $product->rating = $rating;
//                    }else{
//                        $product->rating = new Rating();
//                    }
//                }
//                break;
//            case 'all':
//
//                break;
//        }
//    }
//
//    public static function images(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $images = (new Image())
//                        ->query()
//                        ->select()
//                        ->where('object_id = :object_id AND module_id = :module_id', [':object_id' => $product->id, ':module_id' => self::$module_id])
//                        ->order('position')
//                        ->execute()
//                        ->all(['folder' => 'products'], 'id')
//                        ->getResult();
//
//                    if($images) {
//                        foreach($images as $image){
//                            if(empty($product->images)){
//                                $product->image = $image;
//                            }
//                            $product->images[] = $image;
//                        }
//                    }
//                }
//                break;
//            case 'all':
//                $products = $collection->getResult();
//                $products_ids = $collection->getField('id');
//                $products_map = $collection->toMap()->getMap();
//
//                if($products){
//                    $images = (new Image())
//                        ->query()
//                        ->select()
//                        ->where('object_id IN (' . implode(',', $products_ids) . ') AND module_id = :module_id', [':module_id' => self::$module_id])
//                        ->order('position')
//                        ->execute()
//                        ->all(['folder' => 'products'])
//                        ->getResult();
//
//                    if($images) {
//                        foreach($images as $image){
//                            if(isset($products_map[$image->object_id])){
//                                if(empty($products_map[$image->object_id]->images)){
//                                    $products_map[$image->object_id]->image = $image;
//                                }
//                                $products_map[$image->object_id]->images[] = $image;
//                            }
//                        }
//
//                        foreach($products as $product){
//                            if(isset($rules['rules']['resize'])){
//                                $product->image->res($rules['rules']['resize']['width'], $rules['rules']['resize']['height']);
//                            }
//                        }
//                    }
//                }
//                break;
//        }
//    }
//
//    public static function variants(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $variants = (new Variant())
//                        ->query()
//                        ->select()
//                        ->where('product_id = :product_id', [':product_id' => $product->id])
//                        ->order('position')
//                        ->execute()
//                        ->all(null, 'id')
//                        ->getResult();
//
//                    if($variants) {
//                        foreach ($variants as $variant) {
//                            if (empty($product->variants)) {
//                                $product->variant = $variant;
//                            }
//                            $product->variants[$variant->id] = $variant;
//                        }
//                        $product->variants_count = count($variants);
//                    }
//                }
//                break;
//            case 'all':
//                $products = $collection->getResult();
//                $products_ids = $collection->getId();
//                $products_map = [];
//
//                if($products){
//                    $variants = (new Variant())
//                        ->query()
//                        ->select()
//                        ->where('product_id IN (' . implode(',', $products_ids) . ')')
//                        ->order('position')
//                        ->execute()
//                        ->all(null)
//                        ->getResult();
//
//                    foreach($products as $product){
//                        $products_map[$product->id] = $product;
//                    }
//                    if($variants) {
//                        foreach($variants as $variant){
//                            if(isset($products_map[$variant->product_id])){
//                                if(empty($products_map[$variant->product_id]->variants)){
//                                    $products_map[$variant->product_id]->variant = $variant;
//                                }
//                                $products_map[$variant->product_id]->variants[$variant->id] = $variant;
//                            }
//                        }
//                    }
//
//                }
//
//                break;
//        }
//    }
//
//    public static function visibleVariants(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $variants = (new Variant())
//                        ->query()
//                        ->select()
//                        ->where('is_visible = 1 AND product_id = :product_id', [':product_id' => $product->id])
//                        ->order('position')
//                        ->execute()
//                        ->all(null, 'id')
//                        ->getResult();
//
//                    if($variants) {
//                        foreach ($variants as $variant) {
//                            if (empty($product->variants)) {
//                                $product->variant = $variant;
//                            }
//                            $product->variants[$variant->id] = $variant;
//                        }
//                        $product->variants_count = count($variants);
//                    }
//                }
//                break;
//            case 'all':
//                $products = $collection->getResult();
//                $products_ids = $collection->getId();
//                $products_map = [];
//
//                if($products){
//                    $variants = (new Variant())
//                        ->query()
//                        ->select()
//                        ->where('is_visible = 1 AND product_id IN (' . implode(',', $products_ids) . ')')
//                        ->order('position')
//                        ->execute()
//                        ->all(null)
//                        ->getResult();
//
//                    foreach($products as $product){
//                        $products_map[$product->id] = $product;
//                    }
//
//                    if($variants) {
//                        foreach($variants as $variant){
//                            if(isset($products_map[$variant->product_id])){
//                                if(empty($products_map[$variant->product_id]->variants)){
//                                    $products_map[$variant->product_id]->variant = $variant;
//                                }
//                                $products_map[$variant->product_id]->variants[$variant->id] = $variant;
//                            }
//                        }
//                    }
//                }
//                break;
//        }
//    }
//
//    public static function visibleBadges(Collection $collection, $rules = null)
//    {
//        switch($rules['type']){
//            case 'one':
//                $product = $collection->getResult();
//
//                if($product){
//                    $badges_products_collection = (new BadgeProduct())
//                        ->query()
//                        ->select()
//                        ->where('product_id = :product_id', [':product_id' => $product->id])
//                        ->execute()
//                        ->all(null, 'id');
//
//                    $badges_products_ids = $badges_products_collection->getField('badge_id');
//
//                    if($badges_products_ids){
//                        $badges = (new Badge())
//                            ->query()
//                            ->select()
//                            ->where('is_visible = 1 AND id IN (' . implode(',', $badges_products_ids) . ')')
//                            ->execute()
//                            ->all(null, 'id')
//                            ->getResult();
//
//                        if($badges){
//                            $product->badges = $badges;
//                        }
//                    }
//                }
//                break;
//            case 'all':
//                $products = $collection->getResult();
//                $products_ids = $collection->getId();
//
//                if($products){
//                    $badges_products_collection = (new BadgeProduct())
//                        ->query()
//                        ->select()
//                        ->where('product_id IN (' . implode(',', $products_ids) . ')')
//                        ->execute()
//                        ->all(null, 'id');
//
//                    $badges_products = $badges_products_collection->getResult();
//                    $badges_products_ids = $badges_products_collection->getField('badge_id');
//
//                    if ($badges_products_ids) {
//                        $badges = (new Badge())
//                            ->query()
//                            ->select()
//                            ->where('is_visible = 1 AND id IN (' . implode(',', $badges_products_ids) . ')')
//                            ->execute()
//                            ->all(null, 'id')
//                            ->getResult();
//                    }
//
//
//
//                    foreach ($badges_products as $badge) {
//                        $products[$badge->product_id]->badges[] = $badges[$badge->badge_id];
//                    }
//                }
//                break;
//        }
//    }
//
//    public function saveVariants($varaints = null)
//    {
//        if($varaints){
//            $saved_variants = [];
//
//            $k = 0;
//            foreach($varaints as $varaint){
//                //переписать на нормальную валидацию
//                if(!empty($varaint['name']) || !empty($varaint['sku']) || !empty($varaint['price']) || $k === 0) {
//                    if (array_key_exists($varaint['id'], $this->variants)) {
//                        $this->variants[$varaint['id']]->name = $varaint['name'];
//                        $this->variants[$varaint['id']]->setSku($varaint['sku']);
//                        $this->variants[$varaint['id']]->setPrice($varaint['price']);
//                        $this->variants[$varaint['id']]->setPriceOld($varaint['price_old']);
//                        $this->variants[$varaint['id']]->setStock($varaint['stock']);
//                        $this->variants[$varaint['id']]->setIsVisible($varaint['is_visible']);
//                        $this->variants[$varaint['id']]->position = $k;
//                        $this->variants[$varaint['id']]->update();
//                        $saved_variants[] = $varaint['id'];
//                    } else {
//                        $new_variant = new Variant();
//                        $new_variant->product_id = $this->id;
//                        $new_variant->name = $varaint['name'];
//                        $new_variant->setSku($varaint['sku']);
//                        $new_variant->setPrice($varaint['price']);
//                        $new_variant->setPriceOld($varaint['price_old']);
//                        $new_variant->setStock($varaint['stock']);
//                        $new_variant->setIsVisible($varaint['is_visible']);
//                        $new_variant->position = $k;
//                        $new_variant->insert();
//                    }
//                    $k++;
//                }
//            }
//
//            foreach ($this->variants as $varaint) {
//                if(!in_array($varaint->id, $saved_variants)){
//                    $varaint->delete();
//                }
//            }
//        }
//    }
//
//    public function saveTags($tags)
//    {
//
//        if($tags){
//            $saved_tags = [];
//            foreach($tags as $group_id => $tags_list){
//                if (empty($tags_list))
//                    continue;
//
//                foreach($tags_list as $tag_name){
//                    $tag = (new Tag())
//                        ->query()
//                        ->distinct()
//                        ->select()
//                        ->where('group_id = :group_id AND name = :name', [':group_id' => $group_id, 'name' => $tag_name])
//                        ->limit()
//                        ->execute()
//                        ->one()
//                        ->getResult();
//
//                    if ($tag) {
//                        $saved_tags[] = $tag->id;
//                    }else{
//                        $tag = new Tag();
//                        $tag->group_id = $group_id;
//                        $tag->name = $tag_name;
//                        $tag->is_enabled = 1;
//                        $tag->insert();
//
//                        $saved_tags[] = $tag->id;
//                    }
//                }
//            }
//
//
//            $is_exists_tags = [];
//            foreach ($this->tags as $tag) {
//                if(!in_array($tag->id, $saved_tags)){
//                    (new TagProduct())
//                        ->query()
//                        ->delete()
//                        ->where('tag_id = :tag_id AND product_id = :product_id', [':tag_id' => $tag->id, ':product_id' => $this->id])
//                        ->execute();
//
//                    //проверить тег на наличие в других товарах
//                    $is_exists[] = $tag->id;
//                }
//            }
//
//            foreach($saved_tags as $tag_id){
//                if(!array_key_exists($tag_id, $this->tags)){
//                    $tag_product = (new TagProduct());
//                    $tag_product->tag_id = $tag_id;
//                    $tag_product->product_id = $this->id;
//                    $tag_product->insert();
//                }
//            }
//
//            if($is_exists_tags){
//                (new Tag())
//                    ->query()
//                    ->delete()
//                    ->where('(SELECT COUNT(tp.id)
//                                FROM mc_tags_products tp
//                                WHERE tp.tag_id=mc_tags.id) = 0 AND
//                            (SELECT COUNT(tc.id)
//                                FROM mc_tags_categories tc
//                                WHERE tc.tag_id = mc_tags.id) = 0 AND
//                                mc_tags.id IN (' . implode(',', $is_exists_tags) . ')')
//                    ->execute();
//            }
//        }
//    }
//
//    public function saveAutoTags()
//    {
//        if(isset($this->brand_id)){
//            $brand = (new Brand())
//                ->query()
//                ->select()
//                ->where('id = :id', [':id' => $this->brand_id])
//                ->limit()
//                ->execute()
//                ->one()
//                ->getResult();
//
//            if($brand && $brand->tag_id){
//                $tag_product = (new TagProduct());
//                $tag_product->product_id = $this->id;
//                $tag_product->tag_id = $brand->tag_id;
//                $tag_product->insert(true);
//            }
//        }
//    }
//
//    public function saveCategories($categories_ids)
//    {
//        $product_categories = $this
//            ->query()
//            ->select(['category_id'])
//            ->leftJoin('mc_products_categories', 'mc_products.id = mc_products_categories.product_id')
//            ->where('mc_products.id = :product_id', [':product_id' => $this->id])
//            ->execute()
//            ->all(null, 'category_id')
//            ->getResult();
//
//        if($categories_ids){
//            $saved_categories = [];
//
//            $k = 0;
//            foreach ($categories_ids as $category_id) {
//                if(!empty($category_id)){
//                    if(array_key_exists($category_id, $product_categories)){
//                        (new ProductCategory())
//                            ->query()
//                            ->update(['position' => $k])
//                            ->where('product_id = :product_id AND category_id = :category_id', [':product_id' => $this->id, ':category_id' => $category_id])
//                            ->execute();
//                        $saved_categories[] = $category_id;
//                    }else{
//                        $product_category = new ProductCategory();
//                        $product_category->product_id = $this->id;
//                        $product_category->category_id = $category_id;
//                        $product_category->position = $k;
//                        $product_category->insert();
//                    }
//                    $k++;
//                }
//            }
//
//            foreach ($product_categories as $product_category) {
//                if(!in_array($product_category->category_id, $saved_categories)){
//                    (new ProductCategory())
//                        ->query()
//                        ->delete()
//                        ->where('product_id = :product_id AND category_id = :category_id', [':product_id' => $this->id, ':category_id' => $product_category->category_id])
//                        ->execute();
//                }
//            }
//        }
//    }
//
//    public function removeAutoTags()
//    {
//        //проверяем существует ли в id товара
//        //есть ли в базе уже этот товар
//        if(isset($this->id)){
//            //удаляем старые автотеги товара
//            $product_tags_ids = (new TagProduct())
//                ->query()
//                ->select(['tag_id'])
//                ->from('mc_tags_products AS tp')
//                ->innerJoin('mc_tags AS t', 'tp.tag_id = t.id')
//                ->where('product_id = :product_id AND is_auto = 1', [':product_id' => $this->id])
//                ->execute()
//                ->all(null, 'id')
//                ->getField('tag_id');
//
//            if(!empty($product_tags_ids)){
//                (new TagProduct())
//                    ->query()
//                    ->delete()
//                    ->where('product_id = :product_id AND tag_id IN (' . implode(',', $product_tags_ids) . ')', [':product_id' => $this->id])
//                    ->execute();
//            }
//
//            if(isset($this->brand_id)){
//                $brand = (new Brand())
//                    ->query()
//                    ->select()
//                    ->where('id = :id', [':id' => $this->brand_id])
//                    ->limit()
//                    ->execute()
//                    ->one()
//                    ->getResult();
//
//                if($brand && $brand->tag_id){
//                    (new TagProduct())
//                        ->query()
//                        ->delete()
//                        ->where('product_id = :product_id AND tag_id = :tag_id', [':product_id' => $this->id, ':tag_id' => $brand->tag_id])
//                        ->execute();
//                }
//
//            }
//        }
//    }
//
////    public function filter()
////    {
////        /*$condition = '';
////        $bind = [];
////
////        $pagination = (new Pagination())->by();
////
////        $order = isset($_REQUEST['order']) ? $this->getCore()->request->request('order') : 'price';
////        $order_type = isset($_REQUEST['order_type']) ? $this->getCore()->request->request('order_type') : 'ASC';
////
////        $sep = ' AND ';
////        $i = 0;
////
////        if(isset($_REQUEST['category_id'])){
////            $cond = ' category_id = ' . $_REQUEST['category_id'];
////            $condition .= $i ? $sep . $cond : $cond;
////            $bind['category_id'] = $this->getCore()->request->request('category_id');
////            $i++;
////        }
////
////        if(isset($_REQUEST['category_id'])){
////            $cond = ' category_id = ' . $_REQUEST['category_id'];
////            $condition .= $i ? $sep . $cond : $cond;
////            $bind['category_id'] = $this->getCore()->request->request('category_id');
////            $i++;
////        }
////
////        $products = $this
////            ->query()
////            ->select()
////            ->where($condition, $bind)
////            ->order($order, $order_type)
////            ->limit($pagination['limit'], $pagination['offset'])
////            ->execute()
////            ->all()
////            ->getResult();
////
////        return $products;
////
////        //$this->load();*/
////    }
//
//
//
//
//
//
//
//
//
//
//    /**
//     * Функция возвращает товары
//     * Возможные значения фильтра:
//     * id - id товара или их массив
//     * category_id - id категории или их массив
//     * brand_id - id бренда или их массив
//     * page - текущая страница, integer
//     * limit - количество товаров на странице, integer
//     * sort - порядок товаров, возможные значения: position(по умолчанию), name, price
//     * keyword - ключевое слово для поиска
//     * features - фильтр по свойствам товара, массив (id свойства => значение свойства)
//     */
//    public function get_products($filter = array())
//    {
//        // По умолчанию
//        $limit = 10000;
//        $page = 1;
//        $category_id_filter = '';
//        $brand_id_filter = '';
//        $product_id_filter = '';
//        $product_url_filter = '';
//        //$features_filter = '';
//        $keyword_filter = '';
//        $is_visible_filter = '';
//        //$discounted_filter = '';
//        $in_stock_filter = '';
//        //$order = 'pv.price';
//
//        $tags_tables = '';
//        $tags_filter = '';
//
//        $order = '';
//        $order_direction = '';
//        $exception_ids_filter = '';
//
//        if(isset($filter['limit']))
//            $limit = max(1, intval($filter['limit']));
//
//        if(isset($filter['page']))
//            $page = max(1, intval($filter['page']));
//
//        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
//
//        if(!empty($filter['id']))
//            $product_id_filter = $this->db->placehold('AND p.id in(?@)', (array)$filter['id']);
//
//        if(!empty($filter['url']))
//            $product_url_filter = $this->db->placehold('AND p.url=?', mb_substr($filter['url'], 0, mb_strlen($filter['url'], 'utf-8') - mb_strlen($this->settings->postfix_product_url, 'utf-8'), 'utf-8'));
//
//        if(!empty($filter['category_id']))
//            $category_id_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id = p.id AND pc.category_id in(?@)', (array)$filter['category_id']);
//
//        if(!empty($filter['brand_id']))
//            $brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);
//
//        if(isset($filter['in_stock']))
//            $in_stock_filter = $this->db->placehold('AND (SELECT 1 FROM __variants pv WHERE pv.product_id=p.id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock<>0) LIMIT 1) = ?', intval($filter['in_stock']));
//
//        if(!empty($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=? AND cats.is_visible=? AND pcats.position=0', intval($filter['is_visible']), intval($filter['is_visible']));
//
//        if ($this->settings->catalog_use_smart_sort)
//        {
//            $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Есть в наличии", 1);
//            $stock_group_id = $this->db->result('id');
//
//            $this->db->query("SELECT id FROM __tags WHERE name=? AND group_id=? AND is_auto=?", "да", $stock_group_id, 1);
//            $yes_stock = $this->db->result('id');
//
//            $this->db->query("SELECT id FROM __tags WHERE name=? AND group_id=? AND is_auto=?", "нет", $stock_group_id, 1);
//            $no_stock = $this->db->result('id');
//
//            $this->db->query("SELECT id FROM __tags WHERE name=? AND group_id=? AND is_auto=?", "под заказ", $stock_group_id, 1);
//            $order_stock = $this->db->result('id');
//
//            $order = $this->db->placehold("(SELECT COUNT(*) FROM __tags_products tp1_order WHERE tp1_order.product_id=p.id AND tp1_order.tag_id=?) DESC, (SELECT COUNT(*) FROM __tags_products tp2_order WHERE tp2_order.product_id=p.id AND tp2_order.tag_id=?) DESC, (SELECT COUNT(*) FROM __tags_products tp3_order WHERE tp3_order.product_id=p.id AND tp3_order.tag_id=?) DESC ", $yes_stock, $order_stock, $no_stock);
//        }
//
//        if (!empty($filter['sort']))
//            switch($filter['sort'])
//            {
//                case 'name':
//                    if (!empty($order))
//                        $order .= ', ';
//                    $order .= 'p.name';
//                    break;
//                case 'position':
//                    if (!empty($order))
//                        $order .= ', ';
//                    $order .= 'p.position';
//                    break;
//                case 'price':
//                    $this->db->query("SELECT id FROM __tags_groups WHERE name=? AND is_auto=?", "Цена", 1);
//                    $price_group_id = $this->db->result('id');
//
//                    if (!empty($order))
//                        $order .= ', ';
//                    $order .= '(SELECT MIN(CONVERT(t.name, UNSIGNED)) FROM __tags t INNER JOIN __tags_products tp ON t.id=tp.tag_id WHERE tp.product_id=p.id AND t.group_id='.$price_group_id.')';
//                    break;
//                case 'newest':
//                    if (!empty($order))
//                        $order .= ', ';
//                    $order .= 'p.created_dt';
//                    break;
//            }
//        else
//        {
//            if (!empty($order))
//                $order .= ', ';
//            $order .= 'p.position';
//        }
//
//        if (!empty($filter['sort_type']))
//            switch($filter['sort_type'])
//            {
//                case 'asc':
//                    $order_direction = '';
//                    break;
//                case 'desc':
//                    $order_direction = 'desc';
//                    break;
//            }
//
//        $inner_join_search_table_product = "";
//        if(!empty($filter['keyword']))
//        {
//            $keyword_filter = "AND ((MATCH(ps.`text`) AGAINST('*".mysql_real_escape_string(trim($filter['keyword']))."*' IN BOOLEAN MODE) > 0
//                AND ps.`text` LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%') OR pv.sku LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%' OR pv.sku_in LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%' OR pv.name LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%'";
//
//            if ($this->settings->use_product_id)
//                $keyword_filter .= ' OR p.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'"';
//
//            $keyword_filter .= ")";
//
//            /*$keyword_filter = "AND (";
//            if ($this->settings->use_product_id)
//                $keyword_filter .= 'p.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR ';
//            $keyword_filter .= 'p.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR p.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.sku LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.sku_in LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ';
//            */
//            $inner_join_search_table_product = "INNER JOIN mc_products_search ps on p.id = ps.id";
//        }
//
//        if (!empty($filter['exception']))
//            $exception_ids_filter = $this->db->placehold('AND p.id not in(?@)', (array)$filter['exception']);
//
//        if (isset($filter['tags']))
//        {
//            $q_index = 0;
//            $tables = array();
//            $where = array();
//
//            foreach($filter['tags'] as $group_id=>$tags)
//            {
//                $q_index++;
//                $tables[] = "INNER JOIN __tags_products tp$q_index ON p.id=tp$q_index.product_id
//                    INNER JOIN __tags t$q_index ON tp$q_index.tag_id=t$q_index.id ";
//                if (is_array($tags) && !empty($tags))
//                    $where[] = "AND (t$q_index.group_id = $group_id AND tp$q_index.tag_id in (".join(",",$tags).")) ";
//                else
//                    $where[] = $this->db->placehold("AND (t$q_index.group_id = $group_id AND CONVERT(?,DECIMAL(10,0))<=CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0)) AND CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0))<=CONVERT(?,DECIMAL(10,0)))", $tags->from, $tags->to);
//            }
//            foreach($tables as $t)
//                $tags_tables .= $t;
//
//            foreach($where as $w)
//                $tags_filter .= $w;
//        }
//
//        $query = "SELECT
//                    distinct p.id,
//                    p.url,
//                    p.brand_id,
//                    p.name,
//                    p.annotation,
//                    p.annotation2,
//                    p.body,
//                    p.position,
//                    unix_timestamp(p.created_dt) created_dt,
//                    p.is_visible,
//                    p.meta_title,
//                    p.meta_keywords,
//                    p.meta_description,
//                    b.name as brand,
//                    b.url as brand_url,
//                    p.source,
//                    p.opened_counter,
//                    p.like_click,
//                    p.like_opened,
//                    p.like_buy,
//                    p.original_url,
//                    p.flag,
//                    unix_timestamp(p.updated_dt) updated_dt,
//                    p.css_class,
//                    p.currency_id,
//                    p.use_variable_amount,
//                    p.min_amount,
//                    p.max_amount,
//                    p.step_amount,
//                    p.modificators,
//                    p.modificators_groups,
//                    p.modificators_mode,
//                    p.add_field1,
//                    p.add_field2,
//                    p.add_field3,
//                    p.add_flag1,
//                    p.add_flag2,
//                    p.add_flag3
//                FROM __products p
//                $inner_join_search_table_product
//                $category_id_filter
//                $tags_tables
//                LEFT JOIN __variants pv ON p.id = pv.product_id
//                LEFT JOIN __brands b ON p.brand_id = b.id
//                LEFT JOIN __products_categories pcats ON p.id = pcats.product_id
//                LEFT JOIN __categories cats ON pcats.category_id=cats.id
//                WHERE
//                    1
//                    $product_id_filter
//                    $product_url_filter
//                    $brand_id_filter
//                    $keyword_filter
//                    $in_stock_filter
//                    $is_visible_filter
//                    $exception_ids_filter
//                    $tags_filter
//                ORDER BY $order $order_direction
//                    $sql_limit";
//
//        $query = $this->db->placehold($query);
//        //echo $query;
//        $this->db->query($query);
//
//        return $this->db->results();
//    }
//
//    /**
//     * Функция возвращает количество товаров
//     * Возможные значения фильтра:
//     * category_id - id категории или их массив
//     * brand_id - id бренда или их массив
//     * keyword - ключевое слово для поиска
//     * features - фильтр по свойствам товара, массив (id свойства => значение свойства)
//     */
//    public function count_products($filter = array())
//    {
//        $category_id_filter = '';
//        $brand_id_filter = '';
//        $keyword_filter = '';
//        $is_visible_filter = '';
//        //$discounted_filter = '';
//        //$features_filter = '';
//        $exception_ids_filter = '';
//        $in_stock_filter = '';
//
//        $tags_tables = '';
//        $tags_filter = '';
//
//        if(!empty($filter['category_id']))
//            $category_id_filter = $this->db->placehold('INNER JOIN __products_categories pc ON pc.product_id = p.id AND pc.category_id in(?@)', (array)$filter['category_id']);
//
//        if(!empty($filter['brand_id']))
//            $brand_id_filter = $this->db->placehold('AND p.brand_id in(?@)', (array)$filter['brand_id']);
//
//        $inner_join_search_table_product = "";
//        if(!empty($filter['keyword']))
//        {
//            $keyword_filter = "AND ((MATCH(ps.`text`) AGAINST('*".mysql_real_escape_string(trim($filter['keyword']))."*' IN BOOLEAN MODE) > 0
//                AND ps.`text` LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%') OR pv.sku LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%' OR pv.sku_in LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%' OR pv.name LIKE '%".mysql_real_escape_string(trim($filter['keyword']))."%'";
//
//            if ($this->settings->use_product_id)
//                $keyword_filter .= ' OR p.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'"';
//
//            $keyword_filter .= ")";
//
//            /*$keyword_filter = "AND (";
//            if ($this->settings->use_product_id)
//                $keyword_filter .= 'p.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR ';
//            $keyword_filter .= 'p.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR p.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.sku LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.sku_in LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR pv.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ';
//            */
//            $inner_join_search_table_product = "INNER JOIN mc_products_search ps on p.id = ps.id";
//        }
//
//        if(!empty($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=? AND cats.is_visible=? AND pcats.position=0', intval($filter['is_visible']), intval($filter['is_visible']));
//
//        if(!empty($filter['in_stock']))
//            $in_stock_filter = $this->db->placehold('AND (SELECT 1 FROM __variants pv WHERE pv.product_id=p.id AND pv.price>0 AND (pv.stock IS NULL OR pv.stock>0 OR pv.stock<0) LIMIT 1) = ?', intval($filter['in_stock']));
//
//        //if(!empty($filter['features']) && !empty($filter['features']))
//        //    foreach($filter['features'] as $feature=>$value)
//        //        $features_filter .= $this->db->placehold('AND p.id in (SELECT product_id FROM __options WHERE feature_id=? AND value=? ) ', $feature, $value);
//
//        if (!empty($filter['exception']))
//            $exception_ids_filter = $this->db->placehold('AND p.id not in(?@)', (array)$filter['exception']);
//
//        if (isset($filter['tags']))
//        {
//            $q_index = 0;
//            $tables = array();
//            $where = array();
//
//            foreach($filter['tags'] as $group_id=>$tags)
//            {
//                $q_index++;
//                $tables[] = "INNER JOIN __tags_products tp$q_index ON p.id=tp$q_index.product_id
//                    INNER JOIN __tags t$q_index ON tp$q_index.tag_id=t$q_index.id ";
//                if (is_array($tags) && !empty($tags))
//                    $where[] = "AND (t$q_index.group_id = $group_id AND tp$q_index.tag_id in (".join(",",$tags).")) ";
//                else
//                    $where[] = $this->db->placehold("AND (t$q_index.group_id = $group_id AND CONVERT(?,DECIMAL(10,0))<=CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0)) AND CONVERT(replace(t$q_index.name,' ',''),DECIMAL(10,0))<=CONVERT(?,DECIMAL(10,0)))", $tags->from, $tags->to);
//            }
//            foreach($tables as $t)
//                $tags_tables .= $t;
//
//            foreach($where as $w)
//                $tags_filter .= $w;
//        }
//
//        $query = "SELECT count(distinct p.id) as count
//                FROM __products AS p
//                $inner_join_search_table_product
//                $tags_tables
//                LEFT JOIN __variants pv ON p.id = pv.product_id
//                LEFT JOIN __products_categories pcats ON p.id = pcats.product_id
//                LEFT JOIN __categories cats ON pcats.category_id=cats.id
//                $category_id_filter
//                WHERE 1
//                    $tags_filter
//                    $brand_id_filter
//                    $keyword_filter
//                    $is_visible_filter
//                    $in_stock_filter
//                    $exception_ids_filter";
//
//        $this->db->query($query);
//        return $this->db->result('count');
//    }
//
//
//    /**
//     * Функция возвращает товары удовлетворяющие заданным тегам
//     * Возможные значения фильтра:
//     * page - текущая страница, integer
//     * limit - количество товаров на странице, integer
//     * sort - порядок товаров, возможные значения: position(по умолчанию), name, price
//     * tags - теги
//     */
//    public function get_products_with_tags($filter = array(), $generate_random = false)
//    {
//        // По умолчанию
//        $limit = 10000;
//        $page = 1;
//        $is_visible_filter = '';
//
//        // Инициализация тегов
//        $tags_filter = '';
//        $tags_tables = '';
//
//        $q_index = 0;
//        $where = array();
//        $tables = array();
//
//        $tags_groups = $this->tags->get_taggroups();
//
//        foreach($tags_groups as $group)
//        {
//            if (isset($filter['tags'][$group->id]) && !empty($filter['tags'][$group->id]))
//            {
//                $q_index++;
//                $where[] = "AND (tv$q_index.group_id = $group->id AND tp$q_index.tag_id in (".join(",",$filter['tags'][$group->id]).")) ";
//                $tables[] = "INNER JOIN __tags_products tp$q_index ON p.id=tp$q_index.product_id
//                                INNER JOIN __tags_values tv$q_index ON tp$q_index.tag_id=tv$q_index.id ";
//            }
//        }
//
//        foreach($tables as $t)
//            $tags_tables .= $t;
//
//        foreach($where as $w)
//            $tags_filter .= $w;
//        // Инициализация тегов (End)
//
//        $order = 'pv.price';
//
//        if(isset($filter['limit']))
//            $limit = max(1, intval($filter['limit']));
//
//        if(isset($filter['page']))
//            $page = max(1, intval($filter['page']));
//
//        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
//
//        if(!empty($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=? AND (cats.is_visible=? OR cats.id is null)', intval($filter['is_visible']), intval($filter['is_visible']));
//
//        if(!empty($filter['sort']))
//            switch ($filter['sort'])
//            {
//                case 'position':
//                    $order = 'p.position DESC';
//                    break;
//                case 'name':
//                    $order = 'p.name';
//                    break;
//                case 'created_dt':
//                    $order = 'p.created_dt DESC';
//                    break;
//                case 'price':
//                    $order = 'pv.price';
//                    break;
//            }
//
//        $query = "SELECT
//                    distinct p.id,
//                    p.url,
//                    p.brand_id,
//                    p.name,
//                    p.annotation,
//                    p.annotation2,
//                    p.body,
//                    p.position,
//                    unix_timestamp(p.created_dt) created_dt,
//                    p.is_visible,
//                    p.meta_title,
//                    p.meta_keywords,
//                    p.meta_description,
//                    b.name as brand,
//                    b.url as brand_url,
//                    p.opened_counter,
//                    p.like_click,
//                    p.like_opened,
//                    p.like_buy,
//                    p.original_url,
//                    p.flag,
//                    unix_timestamp(p.updated_dt) updated_dt,
//                    p.css_class,
//                    p.currency_id,
//                    p.use_variable_amount,
//                    p.min_amount,
//                    p.max_amount,
//                    p.step_amount,
//                    p.modificators,
//                    p.modificators_groups,
//                    p.modificators_mode,
//                    p.add_field1,
//                    p.add_field2,
//                    p.add_field3,
//                    p.add_flag1,
//                    p.add_flag2,
//                    p.add_flag3
//                FROM __products p
//                    LEFT JOIN __brands b ON p.brand_id = b.id
//                    LEFT JOIN __variants pv ON p.id=pv.product_id
//                    LEFT JOIN __products_categories pcats ON p.id = pcats.product_id
//                    LEFT JOIN __categories cats ON pcats.category_id=cats.id
//                    $tags_tables
//                WHERE
//                    1
//                    $is_visible_filter
//                    $tags_filter
//                ORDER BY $order
//                    $sql_limit";
//
//        $query = $this->db->placehold($query);
//
//        $this->db->query($query);
//        $products = $this->db->results();
//
//        if ($generate_random)
//        {
//            $ids = array();
//            $res = array();
//            $kol = 0;
//            $max_size = min($this->settings->mainpage_badge_products_count, count($products));
//            //echo "max_size1=$max_size<br>";
//            while ($kol<$max_size)
//            {
//                $val = rand(0,count($products)-1);
//                if (!in_array($products[$val]->id, $ids))
//                {
//                    $ids[] = $products[$val]->id;
//                    $res[] = $products[$val];
//                    $kol++;
//                }
//            }
//        }
//        else
//            $res = $products;
//
//        return $res;
//    }
//
//
//    /**
//     * Функция возвращает количество товаров удовлетворяющих заданным тегам
//     * Возможные значения фильтра:
//     * tags - фильтр по тегам товара, массив (id свойства => значение свойства)
//     */
//    public function count_products_with_tags($filter = array())
//    {
//        $is_visible_filter = '';
//        $tags_filter = '';
//        $tags_tables = '';
//
//        $q_index = 0;
//        $where = array();
//        $tables = array();
//
//        $tags_groups = $this->tags->get_taggroups();
//
//        foreach($tags_groups as $group)
//        {
//            if (isset($filter['tags'][$group->id]) && !empty($filter['tags'][$group->id]))
//            {
//                $q_index++;
//                $where[] = "AND (tv$q_index.group_id = $group->id AND tp$q_index.tag_id in (".join(",",$filter['tags'][$group->id]).")) ";
//                $tables[] = "INNER JOIN __tags_products tp$q_index ON p.id=tp$q_index.product_id
//                                INNER JOIN __tags_values tv$q_index ON tp$q_index.tag_id=tv$q_index.id ";
//            }
//        }
//
//        foreach($tables as $t)
//            $tags_tables .= $t;
//
//        foreach($where as $w)
//            $tags_filter .= $w;
//
//        if(!empty($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=? AND (cats.is_visible=? OR cats.id is null)', intval($filter['is_visible']), intval($filter['is_visible']));
//
//
//        $query = "SELECT count(distinct p.id) as count
//                FROM __products AS p
//                    LEFT JOIN __products_categories pcats ON p.id = pcats.product_id
//                    LEFT JOIN __categories cats ON pcats.category_id=cats.id
//                $tags_tables
//                WHERE 1
//                    $is_visible_filter
//                    $tags_filter ";
//
//        //echo "<!-- query_count=$query-->";
//
//        $this->db->query($query);
//        return $this->db->result('count');
//    }
//
//
//    /**
//     * Функция возвращает товар по id
//     * @param    $id
//     * @retval    object
//     */
//    public function get_product($id)
//    {
//        if(is_numeric($id))
//            $filter = $this->db->placehold('p.id = ?', $id);
//        else
//            $filter = $this->db->placehold('p.url = ?', mb_substr($id, 0, mb_strlen($id, 'utf-8') - mb_strlen($this->settings->postfix_product_url, 'utf-8'), 'utf-8'));
//
//        $query = $this->db->placehold("SELECT DISTINCT
//                    p.id,
//                    p.url,
//                    p.brand_id,
//                    p.name,
//                    p.annotation,
//                    p.annotation2,
//                    p.body,
//                    p.position,
//                    unix_timestamp(p.created_dt) created_dt,
//                    p.is_visible,
//                    p.meta_title,
//                    p.meta_keywords,
//                    p.meta_description,
//                    p.source,
//                    p.opened_counter,
//                    p.like_click,
//                    p.like_opened,
//                    p.like_buy,
//                    p.original_url,
//                    p.flag,
//                    unix_timestamp(p.updated_dt) updated_dt,
//                    p.css_class,
//                    p.currency_id,
//                    p.use_variable_amount,
//                    p.min_amount,
//                    p.max_amount,
//                    p.step_amount,
//                    p.modificators,
//                    p.modificators_groups,
//                    p.modificators_mode,
//                    p.add_field1,
//                    p.add_field2,
//                    p.add_field3,
//                    p.add_flag1,
//                    p.add_flag2,
//                    p.add_flag3
//                FROM __products AS p
//                LEFT JOIN __brands b ON p.brand_id = b.id
//                WHERE $filter
//                GROUP BY p.id
//                LIMIT 1", intval($id));
//        $this->db->query($query);
//        $product = $this->db->result();
//
//        return $product;
//    }
//
//    public function update_product($id, $product)
//    {
//        $product = (array) $product;
//
//        if (array_key_exists('brand_id', $product) && $product['brand_id'] == 0)
//            $product['brand_id'] = null;
//        if (array_key_exists('currency_id', $product) && $product['currency_id'] == 0)
//            $product['currency_id'] = null;
//
//        $need_generate_url = false;
//
//        if(isset($product['url']) && empty($product['url']) && isset($product['name']))
//            $need_generate_url = true;
//
//        if (isset($product['url']) && !empty($product['url']))
//        {
//            $product['url'] = $this->furl->generate_url($product['url']);
//            $this->db->query("SELECT count(id) as count FROM __products WHERE url=? AND id<>?", $product['url'], $id);
//            $k = $this->db->result('count');
//            if ($k > 0)
//                $need_generate_url = true;
//        }
//
//        if ($need_generate_url)
//        {
//            $product['url'] = $this->furl->generate_url($product['name']);
//
//            $this->db->query("SELECT count(id) as count from __products WHERE url=?", $product['url']);
//            $k = $this->db->result('count');
//            if ($k > 0)
//                $product['url'] = $this->furl->generate_url($product['url'].'-'.$id);
//        }
//        /*if (array_key_exists('url', $product))
//            $product['url'] = mb_substr($product['url'], 0, mb_strlen($product['url'], 'utf-8') - mb_strlen($this->settings->postfix_product_url, 'utf-8'), 'utf-8');*/
//        $query = $this->db->placehold("UPDATE __products SET ?%, updated_dt=now() WHERE id in (?@) LIMIT ?", $product, (array)$id, count((array)$id));
//        if($this->db->query($query))
//            return $id;
//        else
//            return false;
//    }
//
//    public function add_product($product)
//    {
//        $product = (array) $product;
//
//        if (array_key_exists('brand_id', $product) && $product['brand_id'] == 0)
//            $product['brand_id'] = null;
//        if (array_key_exists('currency_id', $product) && $product['currency_id'] == 0)
//            $product['currency_id'] = null;
//
//        $url_exist = false;
//        $need_generate_url = false;
//
//        if (empty($product['url']))
//            $need_generate_url = true;
//        else
//        {
//            $this->db->query("SELECT count(id) as count FROM __products WHERE url=?", $product['url']);
//            $k = $this->db->result('count');
//            if ($k > 0)
//                $need_generate_url = true;
//        }
//
//        if ($need_generate_url)
//        {
//            $product['url'] = $this->furl->generate_url($product['name']);
//            $this->db->query("SELECT count(id) as count from __products WHERE url=?", $product['url']);
//            $k = $this->db->result('count');
//            if ($k > 0)
//                $url_exist = true;
//        }
//
//        $this->db->query("INSERT INTO __products SET ?%", $product);
//        $id = $this->db->insert_id();
//        $this->db->query("UPDATE __products SET position=id WHERE id=?", $id);
//        if ($url_exist)
//        {
//            $product['url'] = $this->furl->generate_url($product['url'].'-'.$id);
//            $this->db->query("UPDATE __products SET url=? WHERE id=?", $product['url'], $id);
//        }
//        return $id;
//    }
//
//    /*
//    *
//    * Удалить товар
//    *
//    */
//    public function delete_product($id)
//    {
//        if(!empty($id))
//        {
//            // Удаляем варианты
//            /**$variants = $this->variants->get_variants(array('product_id'=>$id));
//            foreach($variants as $v)
//            $this->variants->delete_variant($v->id);**/
//
//            // Удаляем изображения
//            $images = $this->image->get_images('products', $id);
//            foreach($images as $i)
//                $this->image->delete_image('products', $id, $i->id);
//
//            // Удаляем аттачи
//            $attachments = $this->attachments->get_attachments('products', $id);
//            foreach($attachments as $a)
//                $this->attachments->delete_attachment('products', $id, $a->id);
//
//            //Удаляем отзывы
//            $reviews = $this->reviews->get_reviews(array('product_id'=>$id));
//            if ($reviews)
//                foreach($reviews as $review)
//                    $this->reviews->delete_review($review->id);
//
//            // Удаляем категории
//            /**$categories = $this->categories->get_categories(array('product_id'=>$id));
//            foreach($categories as $c)
//            $this->categories->delete_product_category($id, $c->id);**/
//
//            // Удаляем связанные товары
//            /**$related = $this->get_related_products($id);
//            foreach($related as $r)
//            $this->delete_related_product($id, $r->related_id);
//            $this->db->query("DELETE FROM __related_products WHERE related_id=?", $id);
//
//            $this->db->query("DELETE FROM __groups_related_products WHERE product_id=?", $id);**/
//
//            // Удаляем отзывы
//            //$comments = $this->comments->get_comments(array('object_id'=>$id, 'type'=>'product'));
//            //foreach($comments as $c)
//            //    $this->comments->delete_comment($c->id);
//
//            //Удаляем теги
//            $tags_to_check_empty = array();
//            $this->db->query("SELECT * FROM __tags_products WHERE product_id=?", intval($id));
//            foreach($this->db->results() as $t)
//                $tags_to_check_empty[] = $t->tag_id;
//
//            /**$this->db->query("DELETE FROM __tags_products WHERE product_id=?", intval($id));**/
//
//            /*$this->db->query("SELECT t.id FROM __tags t
//                LEFT JOIN __tags_products tp ON t.id=tp.tag_id
//                LEFT JOIN __tags_categories tc ON t.id=tc.tag_id
//                GROUP BY t.id
//                HAVING count(tp.product_id)=0 AND count(tc.category_id)=0");
//            $tags_ids = $this->db->results('id');
//            foreach($tags_ids as $tag_id)
//                $this->tags->delete_tag($tag_id);*/
//
//            if (!empty($tags_to_check_empty))
//                $this->tags->delete_empty_tags($tags_to_check_empty);
//
//            // Удаляем из покупок
//            /**$this->db->query('UPDATE __purchases SET product_id=NULL WHERE product_id=?', intval($id));**/
//
//            // Удаляем товар
//            $query = $this->db->placehold("DELETE FROM __products WHERE id=? LIMIT 1", intval($id));
//            if($this->db->query($query))
//                return true;
//        }
//        return false;
//    }
//
//    public function duplicate_product($id){
//        $product = $this->get_product($id);
//        $product->id = null;
//        $product->created_dt = null;
//
//        // Сдвигаем товары вперед и вставляем копию на соседнюю позицию
//        $this->db->query('UPDATE __products SET position=position+1 WHERE position>?', $product->position);
//        $new_id = $this->products->add_product($product);
//        $this->db->query('UPDATE __products SET position=? WHERE id=?', $product->position+1, $new_id);
//
//        // Очищаем url
//        $this->db->query('UPDATE __products SET url="" WHERE id=?', $new_id);
//
//        // Дублируем категории
//        $categories = $this->categories->get_product_categories($id);
//        foreach($categories as $c)
//            $this->categories->add_product_category($new_id, $c->category_id);
//
//        // Дублируем изображения
//        /*$images = $this->get_images(array('product_id'=>$id));
//        foreach($images as $image)
//            $this->add_image($new_id, $image->filename);*/
//
//        // Дублируем варианты
//        $variants = $this->variants->get_variants(array('product_id'=>$id));
//        foreach($variants as $variant)
//        {
//            $variant->product_id = $new_id;
//            unset($variant->id);
//            if($variant->infinity)
//                $variant->stock = null;
//            unset($variant->infinity);
//            $this->variants->add_variant($variant);
//        }
//
//        // Дублируем свойства
//        /*$options = $this->features->get_options(array('product_id'=>$id));
//        foreach($options as $o)
//            $this->features->update_option($new_id, $o->feature_id, $o->value);*/
//
//        // Дублируем связанные товары
//        $related = $this->get_related_products($id);
//        foreach($related as $r)
//            $this->add_related_product($new_id, $r->related_id);
//
//
//        return $new_id;
//    }
//
//    function get_related_products($filter = array()){
//        $product_id_filter = '';
//        $product_type_filter = '';
//        $is_visible_filter = '';
//
//        if (!empty($filter['product_id']))
//            $product_id_filter = $this->db->placehold('AND rp.product_id in(?@)', (array)$filter['product_id']);
//        else
//            return array();
//
//        if (isset($filter['product_type']))
//            $product_type_filter = $this->db->placehold('AND rp.product_type=?', $filter['product_type']);
//
//        if (isset($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=?', intval($filter['is_visible']));
//
//        $query = $this->db->placehold("SELECT rp.product_id, rp.related_id, rp.position, rp.product_type
//                    FROM __related_products rp
//                        LEFT JOIN __products p ON rp.related_id=p.id
//                    WHERE
//                    1
//                    $product_id_filter
//                    $product_type_filter
//                    $is_visible_filter
//                    ORDER BY position
//                    ");
//
//        $this->db->query($query);
//        return $this->db->results();
//    }
//
//    function count_related_products($filter = array()){
//        $product_id_filter = '';
//        $product_type_filter = '';
//        $is_visible_filter = '';
//
//        if (!empty($filter['product_id']))
//            $product_id_filter = $this->db->placehold('AND rp.product_id in(?@)', (array)$filter['product_id']);
//        else
//            return array();
//
//        if (isset($filter['product_type']))
//            $product_type_filter = $this->db->placehold('AND rp.product_type=?', $filter['product_type']);
//
//        if (isset($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=?', intval($filter['is_visible']));
//
//        $query = $this->db->placehold("SELECT COUNT(rp.related_id) as count
//                    FROM __related_products rp
//                        LEFT JOIN __products p ON rp.related_id=p.id
//                    WHERE
//                    1
//                    $product_id_filter
//                    $product_type_filter
//                    $is_visible_filter");
//
//        $this->db->query($query);
//        return $this->db->result('count');
//    }
//
//    // Функция возвращает связанные товары
//    public function add_related_product($product_id, $related_id, $position=0, $product_type=0){
//        $query = $this->db->placehold("INSERT IGNORE INTO __related_products SET product_id=?, related_id=?, position=?, product_type=?", $product_id, $related_id, $position, $product_type);
//        $this->db->query($query);
//        return $related_id;
//    }
//
//    // Удаление связанного товара
//    public function delete_related_product($product_id, $related_id, $product_type){
//        $query = $this->db->placehold("DELETE FROM __related_products WHERE product_id=? AND related_id=? AND product_type=? LIMIT 1", intval($product_id), intval($related_id), intval($product_type));
//        $this->db->query($query);
//    }
//
//    /*
//    *
//    * Следующий товар
//    *
//    */
//    public function get_next_product($id)
//    {
//        $this->db->query("SELECT position FROM __products WHERE id=? LIMIT 1", $id);
//        $position = $this->db->result('position');
//
//        $this->db->query("SELECT pc.category_id FROM __products_categories pc WHERE product_id=? ORDER BY position LIMIT 1", $id);
//        $category_id = $this->db->result('category_id');
//
//        $query = $this->db->placehold("SELECT id FROM __products p, __products_categories pc
//                                        WHERE pc.product_id=p.id AND p.position>?
//                                        AND pc.position=(SELECT MIN(pc2.position) FROM __products_categories pc2 WHERE pc.product_id=pc2.product_id)
//                                        AND pc.category_id=?
//                                        AND p.is_visible ORDER BY p.position limit 1", $position, $category_id);
//        $this->db->query($query);
//
//        return $this->get_product((integer)$this->db->result('id'));
//    }
//
//    /*
//    *
//    * Предыдущий товар
//    *
//    */
//    public function get_prev_product($id)
//    {
//        $this->db->query("SELECT position FROM __products WHERE id=? LIMIT 1", $id);
//        $position = $this->db->result('position');
//
//        $this->db->query("SELECT pc.category_id FROM __products_categories pc WHERE product_id=? ORDER BY position LIMIT 1", $id);
//        $category_id = $this->db->result('category_id');
//
//        $query = $this->db->placehold("SELECT id FROM __products p, __products_categories pc
//                                        WHERE pc.product_id=p.id AND p.position<?
//                                        AND pc.position=(SELECT MIN(pc2.position) FROM __products_categories pc2 WHERE pc.product_id=pc2.product_id)
//                                        AND pc.category_id=?
//                                        AND p.is_visible ORDER BY p.position DESC limit 1", $position, $category_id);
//        $this->db->query($query);
//
//        return $this->get_product((integer)$this->db->result('id'));
//    }
//
//    /*
//    *
//    *    Вывод хлебных крошек
//    *
//    */
//    public function get_breadcrumbs($id, $type, $show_self_element = true)
//    {
//        $return_str = "";
//        $id = intval($id);
//        if (!$id)
//            $return_str;
//        $product_categories = $this->categories->get_product_categories($id);
//        if (!$product_categories)
//            $return_str;
//        $first_category = reset($product_categories);
//        if (!$first_category)
//            $return_str;
//        $products_module = $this->furl->get_module_by_name('ProductsController');
//        $category = $this->categories->get_category($first_category->category_id);
//        while(true)
//        {
//            $return_str = $this->settings->breadcrumbs_element_open_tag . "<a href='".$this->config->root_url.$products_module->url.$category->url."/' data-type='category'>".$category->name."</a>" . $this->settings->breadcrumbs_element_close_tag . $return_str;
//            if ($category->parent_id == 0)
//                break;
//            $category = $this->categories->get_category($category->parent_id);
//        }
//        $product = $this->get_product($id);
//        $return_str = $this->settings->breadcrumbs_open_tag . $this->settings->breadcrumbs_first_element . $return_str;
//        if ($show_self_element)
//            $return_str .= $this->settings->breadcrumbs_selected_element_open_tag . $product->name . $this->settings->breadcrumbs_selected_element_close_tag;
//        $return_str .= $this->settings->breadcrumbs_close_tag;
//        return $return_str;
//    }
//
//    // Функция вытаскивает дополнительные данные товара для фронтенда
//    public function get_data_for_frontend_products($products)
//    {
//        if (!isset($products))
//            return false;
//        if (!is_array($products))
//            $products = array($products);
//
//        foreach($products as $index=>$product)
//        {
//            $products[$index]->images = (new Image())->get_images('products', $product->id);
//            $products[$index]->image = reset($products[$index]->images);
//            $variants_filter = array('product_id'=>$product->id, 'is_visible'=>1);
//            if ($this->settings->catalog_default_variants_sort == "stock")
//                $variants_filter['sort'] = $this->db->placehold('abs(IFNULL(v.stock, ?)) desc, stock desc', $this->settings->max_order_amount);
//            $products[$index]->variants = (new Variant())->get_variants($variants_filter);
//
//            if ($this->settings->catalog_hide_nostock_variants && count($products[$index]->variants) > 1)
//                foreach($products[$index]->variants as $index2=>$v)
//                    if ($v->stock == 0)
//                        unset($products[$index]->variants[$index2]);
//
//
//            $products[$index]->variant = reset($products[$index]->variants);
//            $products[$index]->badges = (new Badge())->get_product_badges($product->id);
//            $products[$index]->rating = (new Review())->calc_product_rating($product->id);
//
//            // Свойства товара
//            $products[$index]->tags = (new Tag())->get_product_tags($product->id);
//            $products[$index]->tags_groups = array();
//            foreach($products[$index]->tags as $tag)
//            {
//                /*if (!in_array($tag->group_id, $groups_ids))
//                    continue;*/
//                if (!array_key_exists($tag->group_id, $products[$index]->tags_groups))
//                    $products[$index]->tags_groups[$tag->group_id] = array();
//                $products[$index]->tags_groups[$tag->group_id][] = $tag;
//            }
//
//            $products[$index]->reviews_count = (new Review())->count_reviews(array('product_id'=>$product->id, 'is_visible'=>1, 'moderated'=>1));
//
//            if ($this->settings->catalog_show_all_products)
//            {
//                $in_stock = false;
//                foreach($products[$index]->variants as $v)
//                    if ($v->stock == null || $v->stock <> 0)
//                        $in_stock = true;
//                if (!$in_stock)
//                    unset($products[$index]);
//            }
//        }
//
//        return $products;
//    }
//
//##############################
//## FAVORITES PRODUCTS
//##############################
//
//    public function add_favorite_product($product_id, $user_id)
//    {
//        $query = $this->db->placehold("INSERT IGNORE INTO __favorites_products SET product_id=?, user_id=?", $product_id, $user_id);
//        $this->db->query($query);
//        return true;
//    }
//
//    public function get_favorites_products($user_id)
//    {
//        $query = $this->db->placehold("SELECT product_id
//                    FROM __favorites_products
//                    WHERE user_id=?
//                    ORDER BY created_dt", intval($user_id));
//
//        $this->db->query($query);
//        return $this->db->results('product_id');
//    }
//
//    public function count_favorites_products($user_id)
//    {
//        $query = $this->db->placehold("SELECT count(product_id) as kol
//            FROM __favorites_products
//            WHERE user_id=?", intval($user_id));
//
//        $this->db->query($query);
//        return $this->db->result('kol');
//    }
//
//    public function delete_favorite_product($product_id, $user_id)
//    {
//        $query = $this->db->placehold("DELETE FROM __favorites_products WHERE product_id=? AND user_id=? LIMIT 1", intval($product_id), intval($user_id));
//        $this->db->query($query);
//    }
//
//    public function check_favorite_product($product_id, $user_id)
//    {
//        $query = $this->db->placehold("SELECT 1 as k FROM __favorites_products WHERE product_id=? AND user_id=? LIMIT 1",intval($product_id), intval($user_id));
//        $this->db->query($query);
//        return $this->db->result('k') == 1;
//    }
//
//##############################
//## ANALOGS PRODUCTS
//##############################
//    public function add_product_to_new_group_analogs($product_id)
//    {
//        $this->db->query("DELETE FROM __analogs_products WHERE product_id=?", $product_id);
//
//        $this->db->query("SELECT max(group_id) as max_group_id FROM __analogs_products");
//        $group_id = $this->db->result('max_group_id');
//        if (isset($group_id))
//            $group_id = $group_id + 1;
//        else
//            $group_id = 1;
//
//        $group = new stdClass;
//        $group->group_id = $group_id;
//        $group->product_id = $product_id;
//        $group->position = 0;
//
//        $this->db->query("INSERT INTO __analogs_products SET ?%", $group);
//        return $group_id;
//    }
//
//    public function add_product_to_exist_group_analogs($group_id, $product_id)
//    {
//        $this->db->query("SELECT max(position) as max_position FROM __analogs_products WHERE group_id=?", $group_id);
//        $position = $this->db->result('max_position');
//        if (isset($position) && $position !== false)
//            $position = $position + 1;
//        else
//            $position = 0;
//
//        $group = new stdClass;
//        $group->group_id = $group_id;
//        $group->product_id = $product_id;
//        $group->position = $position;
//
//        $this->db->query("INSERT INTO __analogs_products SET ?%", $group);
//        return $group_id;
//    }
//
//    public function delete_product_from_group_analogs($product_id)
//    {
//        $this->db->query("DELETE FROM __analogs_products WHERE product_id=?", $product_id);
//        return 1;
//    }
//
//    public function get_analogs_by_product_id($product_id)
//    {
//        $this->db->query("SELECT group_id FROM __analogs_products WHERE product_id=?", $product_id);
//        $group_id = $this->db->result('group_id');
//        if (!$group_id)
//            return false;
//        $this->db->query("SELECT * FROM __analogs_products WHERE group_id=? ORDER BY position", $group_id);
//        $analogs = $this->db->results();
//        return $analogs;
//    }
//
//    public function get_analog_product_by_product_id($product_id)
//    {
//        $this->db->query("SELECT * FROM __analogs_products WHERE product_id=?", $product_id);
//        return $this->db->result();
//    }
//
//    public function empty_group_analogs($group_id)
//    {
//        $this->db->query("DELETE FROM __analogs_products WHERE group_id=?", $group_id);
//        return 1;
//    }
//
//    function count_analogs_products($filter = array()){
//        $group_id_filter = '';
//        $is_visible_filter = '';
//        $exclude_filter = '';
//
//        if (!empty($filter['group_id']))
//            $group_id_filter = $this->db->placehold('AND ap.group_id in(?@)', (array)$filter['group_id']);
//        else
//            return array();
//
//        if (!empty($filter['exclude_id']))
//            $exclude_filter = $this->db->placehold('AND ap.product_id <> ?', intval($filter['exclude_id']));
//
//        if (isset($filter['is_visible']))
//            $is_visible_filter = $this->db->placehold('AND p.is_visible=?', intval($filter['is_visible']));
//
//        $query = $this->db->placehold("SELECT COUNT(ap.product_id) as count
//                    FROM __analogs_products ap
//                        LEFT JOIN __products p ON ap.product_id=p.id
//                    WHERE
//                    1
//                    $group_id_filter
//                    $is_visible_filter
//                    $exclude_filter");
//
//        $this->db->query($query);
//        return $this->db->result('count');
//    }
//
//    public function count($conditions, $fields)
//    {
//        return $this
//            ->query
//            ->select('count(id) AS count')
//            ->where($conditions, $fields)
//            ->execute()
//            ->all()
//            ->getResult();
//
//        /*if (isset($filter['is_visible'])){
//            $is_visible_filter = $this->db->placehold("AND b.is_visible=?", intval($filter['is_visible']));
//        }
//
//        if(!empty($filter['category_id'])){
//            $category_id_filter = $this->db->placehold('INNER JOIN __products p ON p.brand_id=b.id LEFT JOIN __products_categories pc ON p.id = pc.product_id WHERE pc.category_id in(?@)', (array)$filter['category_id']);
//        }
//
//        if(!empty($filter['keyword'])){
//            $keyword_filter = $this->db->placehold('AND (b.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%" OR b.meta_keywords LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ');
//        }
//
//        if(isset($filter['is_popular'])){
//
//        }
//            $popular_filter = */
//
//
//    }
//}