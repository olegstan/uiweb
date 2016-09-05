<?php
namespace app\models;

use app\layer\LayerModel;
use app\models\image\Image;

class Slideshow extends LayerModel
{
    protected $table = 'mc_slideshow';

    public $image;

    public function __construct()
    {
        $this->image = new Image();
    }


    public function getSlides()
    {
        $slides = (new Slideshow())
            ->query()
            ->select()
            ->where('is_visible = :is_visible', [':is_visible' => 1])
            ->order('position')
            ->execute()
            ->all(null, 'id');

        $slides_ids = $slides->getId();
        $slides = $slides->getResult();

        $images = (new Image())
            ->query()
            ->select()
            ->where('object_id IN (' . implode(',', $slides_ids) . ') AND module_id = 17 AND position = 0')
            ->execute()
            ->all(['folder' => 'slideshow'], 'object_id')
            ->getResult();

        foreach($slides as $k => $slide){
            $slides[$k]->image = $images[$k];
        }

        return $slides;
    }










    public function get_slide($id)
    {
        $query = $this->db->placehold("SELECT id, url, is_visible, position FROM __slideshow WHERE id=? LIMIT 1", intval($id));
        $this->db->query($query);
        return $this->db->result();
    }

    public function get_slides($filter = array())
    {
        $is_visible_filter = '';

        if(isset($filter['is_visible']))
            $is_visible_filter = $this->db->placehold('AND is_visible=?', intval($filter['is_visible']));

        $query = "SELECT id, url, is_visible, position FROM __slideshow WHERE 1 $is_visible_filter ORDER BY position";

        $this->db->query($query);

        return $this->db->results();
    }

    public function update_slide($id, $slide)
    {
        $query = $this->db->placehold("UPDATE __slideshow SET ?% WHERE id in(?@)", $slide, (array)$id);
        $this->db->query($query);
        return $id;
    }

    public function add_slide($slide)
    {
        $query = $this->db->placehold('INSERT INTO __slideshow SET ?%', $slide);
        if(!$this->db->query($query))
            return false;

        $id = $this->db->insert_id();
        $this->db->query("UPDATE __slideshow SET position=id WHERE id=?", $id);
        return $id;
    }

    public function delete_slide($id)
    {
        if(!empty($id))
        {
            $images = $this->image->get_images('slideshow', $id);
            foreach($images as $i)
                $this->image->delete_image('slideshow', $id, $i->id);

            $query = $this->db->placehold("DELETE FROM __slideshow WHERE id=? LIMIT 1", intval($id));
            $this->db->query($query);
        }
    }
}