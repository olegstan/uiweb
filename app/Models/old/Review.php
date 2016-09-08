<?php
namespace app\models;

use app\layer\LayerModel;
use app\models\product\Product;
use app\models\user\User;
use app\models\image\Image;
use core\Collection;
use \DateTime;

class Review extends LayerModel
{
    protected $table = 'mc_reviews';

    public $user;
    public $reviews_count = 0;

    public $module_id = 23;

    /**
     * @var array
     */
    public $images = [];

    public static $days_week = [
        'Monday' => 'Пн',
        'Tuesday' => 'Вт',
        'Wednesday' => 'Ср',
        'Thursday' => 'Чт',
        'Friday' => 'Пт',
        'Saturday' => 'Сб',
        'Sunday' => 'Вс'
    ];

    /**
     * @param Collection $collection
     * @param null $rules
     *
     */

    public function afterSelect()
    {
        $this->created_t = (new DateTime($this->created_dt))->format('H:i:s');
        return $this;
    }

    public static function users(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $reviews = $collection->getResult();

                if($reviews){
                    $users_ids = [];
                    $session_ids = [];

                    foreach($reviews as $review){
                        if($review->user_id){
                            $users_ids[$review->user_id] = $review->user_id;
                        }else{
                            $session_ids[$review->session_id] = $review->session_id;
                        }
                    }

                    if($users_ids){
                        $users = (new User())
                            ->query()
                            ->select()
                            ->where('id IN (' . implode(',', $users_ids) . ')')
                            ->execute()
                            ->all(null, 'user_id')
                            ->getResult();

                        $count_reviews = (new Review())
                            ->query()
                            ->select(['COUNT(*) as reviews_count'])
                            ->where('user_id IN (' . implode(',', $users_ids) . ')')
                            ->group('user_id')
                            ->execute()
                            ->all(null, 'user_id')
                            ->getResult();

                        foreach ($reviews as $review) {
                            if($review->user_id) {
                                $review->user = $users[$review->user_id]->count;
                                $review->reviews_count = $count_reviews[$review->user_id]->count;
                            }
                        }
                    }

                    if($session_ids){
                        $count_reviews = (new Review())
                            ->query()
                            ->select(['COUNT(*) as reviews_count'])
                            ->where('session_id IN ("' . implode('","', $session_ids) . '")')
                            ->group('session_id')
                            ->execute()
                            ->all(null, 'session_id')
                            ->getResult();

                        foreach ($reviews as $review) {
                            if(!$review->user_id) {
                                $review->reviews_count = $count_reviews[$review->session_id]->count;
                            }
                        }
                    }
                }
                break;
        }
    }

    public static function products(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $reviews = $collection->getResult();
                $reviews_ids = $collection->getId();

                if($reviews){
                    $images = (new Image())
                        ->query()
                        ->select()
                        ->where('object_id in (' . implode(',', $reviews_ids) . ') AND module_id = :module_id', [':module_id' => current($reviews)->module_id])
                        ->order('position')
                        ->execute()
                        ->all(['folder' => 'reviews'], 'object_id')
                        ->getResult();

                    if($images) {
                        foreach($images as $k => $image){
                            $reviews[$k]->images[] = $image;
                        }
                    }
                }
                break;
        }
    }

    public function images(Collection $collection, $rules = null)
    {
        switch($rules['type']){
            case 'one':

                break;
            case 'all':
                $reviews = $collection->getResult();
                $products_ids = $collection->getField('product_id');

                if($reviews){
                    $products = (new Product())
                        ->query()
                        ->with(['images'])
                        ->select()
                        ->where('id IN (' . implode(',', $products_ids) . ')')
                        ->execute()
                        ->all(null, 'id')
                        ->getResult();

                    if($products){
                        foreach($reviews as $review){
                            $review->product = $products[$review->product_id];
                        }
                    }

                }

                break;
        }
    }


    public function getDayString(DateTime $today, DateTime $yesterday)
    {
        $created_dt = (new DateTime($this->created_dt))->format('Ymd');

        if ($created_dt == $today->format('Ymd')) {
            $this->day_str = 'Сегодня';
        }else{
            if($created_dt == $yesterday->format('Ymd')){
                $this->day_str = 'Вчера';
            }else{
                $created_year_dt = (new DateTime($this->created_dt))->format('Ymd');
                $created_day_dt = (new DateTime($this->created_dt))->format('l');

                if ($created_year_dt == date('Y')) {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m');
                } else {
                    $this->day_str = self::$days_week[$created_day_dt] . ' ' . (new DateTime($this->created_dt))->format('d.m.Y');
                }
            }
        }
    }
























    public function calc_product_rating($product_id)
    {
        $this->db->query("SELECT (ROUND(AVG(rating) * 2) / 2) as avg_rating, AVG(rating) as avg_rating_real, COUNT(id) as rating_count FROM __reviews WHERE product_id=? AND moderated=1 GROUP BY product_id", $product_id);
        return $this->db->result();
    }

    public function calc_review_rank($review_id){
        $rank = 0;
        $newest_rank = 0;
        $popular_rank = 0;
        $fill_rank = 0;
        $claim_rank = 0;
        $review = $this->get_review($review_id);
        if (!$review)
            return false;
        //calc newest rank
        $day_diff = (time() - $review->datetime) / 60 / 60 / 24;
        if ($day_diff <= 7)
            $newest_rank = 5;
        elseif ($day_diff <= 30)
            $newest_rank = 2;
        elseif ($day_diff <= 180)
            $newest_rank = 1;
        $newest_rank *= $this->settings->koef_newest;
        //calc popular_rank
        $helpful_sub = $review->helpful-$review->nothelpful;
        /*if ($helpful_sub > 0 && $helpful_sub > $review->nothelpful)
            $popular_rank = 5;
        elseif ($helpful_sub > 0)
            $popular_rank = 2;
        elseif ($helpful_sub == 0)
            $popular_rank = 0;
        else
            $popular_rank = -5;*/
        $popular_rank = $helpful_sub;
        $popular_rank *= $this->settings->koef_popular;
        //calc fill rank
        $fill_rank = 1;
        $review_images = $this->image->get_images('reviews', $review_id);
        if ($review_images && $review->name && $review->short && $review->pluses && $review->minuses && $review->comments)
            $fill_rank = 5;
        elseif ($review->name && $review->short && $review->pluses && $review->minuses && $review->comments)
            $fill_rank = 2;
        $fill_rank *= $this->settings->koef_fill;
        //calc claim rank
        $claims_count = $this->count_claims(array('review_id'=>$review_id));
        $claim_rank = $claims_count * $this->settings->koef_claim;

        return array('newest_rank'=>$newest_rank, 'popular_rank'=>$popular_rank, 'fill_rank'=>$fill_rank, 'claim_rank'=>$claim_rank);
    }

    public function get_product_rate_by_user_id($product_id, $user_id)
    {
        $this->db->query("SELECT id, product_id, rating, (ROUND(rating * 2) / 2) as avg_rating, unix_timestamp(datetime) as datetime, user_id, session_id, name, short, pluses, minuses, comments, recommended, is_visible, helpful, nothelpful, moderated, rank FROM __reviews WHERE product_id=? AND user_id=? LIMIT 1", $product_id, $user_id);
        return $this->db->result();
    }

    public function get_product_rate_by_session_id($product_id, $session_id)
    {
        $this->db->query("SELECT id, product_id, rating, (ROUND(rating * 2) / 2) as avg_rating, unix_timestamp(datetime) as datetime, user_id, session_id, name, short, pluses, minuses, comments, recommended, is_visible, helpful, nothelpful, moderated, rank FROM __reviews WHERE product_id=? AND session_id=? LIMIT 1", $product_id, $session_id);
        return $this->db->result();
    }

    public function get_review($id)
    {
        $this->db->query("SELECT id, product_id, rating, unix_timestamp(datetime) as datetime, user_id, session_id, name, short, pluses, minuses, comments, recommended, is_visible, helpful, nothelpful, moderated, rank FROM __reviews WHERE id=? LIMIT 1", $id);
        return $this->db->result();
    }

    public function get_reviews($filter = array())
    {
        // По умолчанию
        $limit = 10000;
        $page = 1;

        $product_id_filter = '';
        $user_id_filter = '';
        $is_visible_filter = '';
        $moderated_filter = '';
        $keyword_filter = '';
        $order = 'rank';
        $order_direction = 'desc';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (isset($filter['product_id']))
            $product_id_filter = $this->db->placehold("AND r.product_id=?", intval($filter['product_id']));

        if (isset($filter['user_id']))
            $user_id_filter = $this->db->placehold("AND r.user_id=?", intval($filter['user_id']));

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold("AND r.is_visible=?", intval($filter['is_visible']));

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold("AND r.moderated=?", intval($filter['moderated']));

        if (!empty($filter['sort']))
            switch($filter['sort']){
                case "id":
                    $order = "r.id";
                    $order_direction = "asc";
                    break;
                case "rank":
                    $order = "r.rank";
                    $order_direction = "desc";
                    break;
                case "date":
                    $order = "r.datetime";
                    $order_direction = "desc";
                    break;
                case "rating":
                    $order = "(r.helpful-r.nothelpful)";
                    $order_direction = "desc";
                    break;
            }
        //$order = $filter['sort'];

        if (!empty($filter['sort_type']))
            $order_direction = $filter['sort_type'];

        if(!empty($filter['keyword']))
        {
            $keyword_filter = 'AND (r.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR ';
            $keyword_filter .= 'r.short LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.pluses LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.minuses LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.comments LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR p.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ';
        }

        $query = $this->db->placehold("SELECT r.id, r.product_id, r.rating, (ROUND(r.rating * 2) / 2) as avg_rating, unix_timestamp(r.datetime) as datetime, r.user_id, r.session_id, r.name, r.short, r.pluses, r.minuses, r.comments, r.recommended, r.is_visible, r.helpful, r.nothelpful, r.moderated, r.rank
            FROM __reviews r
                LEFT JOIN __products p ON r.product_id = p.id
            WHERE 1
                AND r.name<>'' AND r.comments<>''
                $product_id_filter
                $user_id_filter
                $is_visible_filter
                $moderated_filter
                $keyword_filter
            ORDER BY $order $order_direction
            $sql_limit");
        $this->db->query($query);

        return $this->db->results();
    }

    public function count_reviews($filter = array())
    {
        $product_id_filter = '';
        $user_id_filter = '';
        $is_visible_filter = '';
        $moderated_filter = '';
        $session_id_filter = '';
        $keyword_filter = '';

        if (isset($filter['product_id']))
            $product_id_filter = $this->db->placehold("AND r.product_id=?", intval($filter['product_id']));

        if (isset($filter['user_id']))
            $user_id_filter = $this->db->placehold("AND r.user_id=?", intval($filter['user_id']));

        if (isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold("AND r.is_visible=?", intval($filter['is_visible']));

        if (isset($filter['moderated']))
            $moderated_filter = $this->db->placehold("AND r.moderated=?", intval($filter['moderated']));

        if (isset($filter['session_id']))
            $session_id_filter = $this->db->placehold("AND r.session_id=?", $filter['session_id']);

        if(!empty($filter['keyword']))
        {
            $keyword_filter = 'AND (r.id = "'.mysql_real_escape_string(trim($filter['keyword'])).'" OR ';
            $keyword_filter .= 'r.short LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.pluses LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.minuses LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.comments LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR r.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%"
                OR p.name LIKE "%'.mysql_real_escape_string(trim($filter['keyword'])).'%") ';
        }

        $query = $this->db->placehold("SELECT count(r.id) as count
            FROM __reviews r
                LEFT JOIN __products p ON r.product_id = p.id
            WHERE 1
                AND r.name<>'' AND r.comments<>''
                $product_id_filter
                $user_id_filter
                $is_visible_filter
                $moderated_filter
                $session_id_filter
                $keyword_filter");
        $this->db->query($query);

        return $this->db->result('count');
    }

    public function update_review($id, $review){
        $review = (array) $review;

        $review['moderated'] = 1;

        $query = $this->db->placehold("UPDATE __reviews SET ?% WHERE id in(?@)", $review, (array)$id);
        $this->db->query($query);

        return $id;
    }

    public function update_review_rank($id){
        $rank_arr = $this->calc_review_rank($id);

        $query = $this->db->placehold("UPDATE __reviews SET rank=? WHERE id=?", $rank_arr['newest_rank'] + $rank_arr['popular_rank'] + $rank_arr['fill_rank'] - $rank_arr['claim_rank'], $id);
        $this->db->query($query);
    }

    public function add_review($review)
    {
        $review = (array) $review;

        if ($this->settings->reviews_premoderate && !array_key_exists('moderated', $review)){
            $review['moderated'] = 0;
            $review['is_visible'] = 0;
        }

        $query = $this->db->placehold('INSERT IGNORE INTO __reviews SET ?%', $review);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();

        $this->update_review_rank($id);
        /*$rank_arr = $this->calc_review_rank($id);
        $this->update_review($id, array('rank' => $rank_arr['newest_rank'] + $rank_arr['popular_rank'] + $rank_arr['fill_rank'] - $rank_arr['claim_rank']));*/

        return $id;
    }

    public function delete_review($id)
    {
        if(!empty($id))
        {
            // Удаляем изображения
            $images = $this->image->get_images('reviews', $id);
            foreach($images as $i)
                $this->image->delete_image('reviews', $id, $i->id);

            $query = $this->db->placehold("DELETE FROM __reviews WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }

#################################################################################################################
#### ОТВЕТЫ К ОТЗЫВАМ
#################################################################################################################

    public function get_review_response_by_user_id($review_id, $user_id)
    {
        $this->db->query("SELECT id, review_id, unix_timestamp(datetime) as datetime, helpful, nothelpful, user_id, session_id FROM __reviews_responses WHERE review_id=? AND user_id=? LIMIT 1", $review_id, $user_id);
        return $this->db->result();
    }

    public function get_review_response_by_session_id($review_id, $session_id)
    {
        $this->db->query("SELECT id, review_id, unix_timestamp(datetime) as datetime, helpful, nothelpful, user_id, session_id FROM __reviews_responses WHERE review_id=? AND session_id=? LIMIT 1", $review_id, $session_id);
        return $this->db->result();
    }

    public function add_response($response)
    {
        $query = $this->db->placehold('INSERT IGNORE INTO __reviews_responses SET ?%', $response);
        if(!$this->db->query($query))
            return false;

        return $this->db->insert_id();
    }

#################################################################################################################
#### ЖАЛОБЫ К ОТЗЫВАМ
#################################################################################################################

    public function get_review_claim_by_user_id($review_id, $user_id)
    {
        $this->db->query("SELECT id, review_id, unix_timestamp(datetime) as datetime, claim_type, claim_text, user_id, session_id FROM __reviews_claims WHERE review_id=? AND user_id=? LIMIT 1", $review_id, $user_id);
        return $this->db->result();
    }

    public function get_review_claim_by_session_id($review_id, $session_id)
    {
        $this->db->query("SELECT id, review_id, unix_timestamp(datetime) as datetime, claim_type, claim_text, user_id, session_id FROM __reviews_claims WHERE review_id=? AND session_id=? LIMIT 1", $review_id, $session_id);
        return $this->db->result();
    }

    public function get_claims($filter = array())
    {
        // По умолчанию
        $limit = 10000;
        $page = 1;

        $review_id_filter = '';
        $user_id_filter = '';
        $session_id_filter = '';
        $order = 'datetime';
        $order_direction = 'desc';

        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));

        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));

        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);

        if (isset($filter['review_id']))
            $review_id_filter = $this->db->placehold("AND review_id=?", intval($filter['review_id']));

        if (isset($filter['user_id']))
            $user_id_filter = $this->db->placehold("AND user_id=?", intval($filter['user_id']));

        if (isset($filter['session_id']))
            $session_id_filter = $this->db->placehold("AND session_id=?", $filter['session_id']);

        if (!empty($filter['sort']))
            $order = $filter['sort'];

        if (!empty($filter['sort_type']))
            $order_direction = $filter['sort_type'];

        $query = $this->db->placehold("SELECT id, review_id, unix_timestamp(datetime) as datetime, claim_type, claim_text, user_id, session_id
            FROM __reviews_claims
            WHERE 1
                $review_id_filter
                $user_id_filter
                $session_id_filter
            ORDER BY $order $order_direction
            $sql_limit");
        $this->db->query($query);

        return $this->db->results();
    }

    public function count_claims($filter = array())
    {
        $review_id_filter = '';
        $user_id_filter = '';
        $session_id_filter = '';

        if (isset($filter['review_id']))
            $review_id_filter = $this->db->placehold("AND review_id=?", intval($filter['review_id']));

        if (isset($filter['user_id']))
            $user_id_filter = $this->db->placehold("AND user_id=?", intval($filter['user_id']));

        if (isset($filter['session_id']))
            $session_id_filter = $this->db->placehold("AND session_id=?", $filter['session_id']);

        $query = $this->db->placehold("SELECT count(id) as count
            FROM __reviews_claims
            WHERE 1
                $review_id_filter
                $user_id_filter
                $session_id_filter");
        $this->db->query($query);

        return $this->db->result('count');
    }

    public function add_claim($claim)
    {
        $claim = (array) $claim;
        $query = $this->db->placehold('INSERT IGNORE INTO __reviews_claims SET ?%', $claim);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();

        $rank_arr = $this->calc_review_rank($claim['review_id']);
        $this->update_review($claim['review_id'], array('rank' => $rank_arr['newest_rank'] + $rank_arr['popular_rank'] + $rank_arr['fill_rank'] - $rank_arr['claim_rank']));

        return $id;
    }

    public function delete_claim($id)
    {
        if(!empty($id))
        {
            $query = $this->db->placehold("DELETE FROM __reviews_claims WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}