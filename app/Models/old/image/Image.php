<?php
namespace app\models\image;

use app\layer\LayerModel;
use core\helper\DigitToWord;
use core\helper\Translit;
use \Imagick;
use core\db\Database;

class Image extends LayerModel
{
    protected $table = 'mc_images';

    /**
     * @var fields
     */

    public $id;
    public $module_id;
    public $name;
    public $object_id;

    /**
     * @var
     * products
     * catefories etc.
     */
    public $type;

    /**
     * @var
     * image/png
     * image/jpeg etc.
     *
     */

    public $content_type;

    /**
     * @var
     *
     * номера папок для путей
     */

    public $tmp_file;

    public $filename;
    public $path_info;
    public $width = 0;
    public $height = 0;

    public $resized_filename;
    public $resized_width = 0;
    public $resized_height = 0;

    public $is_default = true;

    public $resized_filename_encoded;
    public $position;

    public $guarded = [
        'filename'
    ];

    public function __construct()
    {
        //$this->db = new Database();
    }

    public function defaultImage()
    {
        $this->filename = ABS . $this->getCore()->config['default_image'];

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     *
     * resize
     */

    public function res($width = 0, $height = 0)
    {
        $this->addResizeParams($width, $height);

        $this->resized_filename_encoded = rawurlencode($this->resized_filename);

        if($this->is_default){
            $this->resized_filename = '/files/images/resized/default/' . $this->resized_filename_encoded;
        }else{
            $inner_path = $this->object_id[0] . '/' . $this->object_id[1] . '/';
            $this->resized_filename = '/files/images/resized/' . $this->type . '/' . $inner_path .  $this->resized_filename_encoded;
        }
    }

    public function afterSelect($rules = null)
    {
        $this->type = $rules['folder'];
        $this->is_default = false;

        $this->filename = '/files/images/originals/' . $rules['folder'] . '/' . $this->object_id[0] . '/' . $this->object_id[1] . '/' . $this->filename;

        if(isset($rules['resize'])){
            $this->res($rules['resize']['width'], $rules['resize']['height']);
        }

        return $this;
    }

    public function beforeDelete()
    {
        $this->removeImage();
    }

    public function addResizeParams($width = 0, $height = 0)
    {
        $path_info = pathinfo($this->filename);

        if($width > 0 || $height > 0) {
            $this->resized_filename = $path_info['filename'] . '.' . ($width > 0 ? $width : '') . 'x' . ($height > 0 ? $height : '') . '.' . $path_info['extension'];
        }else {
            $this->resized_filename = $path_info['filename'] . '.' . $path_info['extension'];
        }
    }

    public function getResizeParams()
    {
        // Определаяем параметры ресайза
        if(!preg_match('/([^.]+)\.(([0-9]*)x([0-9]*)(w)?\.)?([^\.]+)$/', $this->resized_filename, $matches))
            return false;

        $file = $matches[1];                    // имя запрашиваемого файла
        //$width = $matches[2];                    // ширина будущего изображения
        $width = $matches[3];                    // ширина будущего изображения
        //$height = $matches[3];                    // высота будущего изображения
        $height = $matches[4];                    // высота будущего изображения
        //$ext = $matches[5];                        // расширение файла
        $ext = $matches[6];                        // расширение файла

        $file_parts = explode('/', $file);
        $file_parts[3] = 'originals';
        $filename = implode('/', $file_parts);

        return [$filename . '.' . $ext, $width, $height];
    }

    public function resize()
    {
        // Если файл удаленный (http://), зальем его себе
        /*if(substr($source_file, 0, 7) == 'http://')
        {
            // Имя оригинального файла
            if(!$original_file = $this->download_image($source_file))
                return false;

            $resized_file = $this->add_resize_params($original_file, $width, $height);
        }
        else
        {
            $original_file = $source_file;
        }*/

        $watermark_offet_x = 50;
        $watermark_offet_y = 50;

        //$sharpen = min(100, $this->settings->images_sharpen)/100;
        //$watermark_transparency =  1-min(100, $this->settings->watermark_transparency)/100;

        /*if((in_array($object, array('products', 'image')) || ($object == 'reviews' && $this->settings->watermark_reviews_is_enabled == 1)) && is_file($this->config->watermark_file) && $this->settings->watermark_is_enabled==1)
            $watermark = $this->config->watermark_file;
        else
            $watermark = null;*/

        $path_info = pathinfo($this->resized_filename);

        if (isset($path_info['dirname']) && !file_exists(ABS . $path_info['dirname'])) {
            //$this->recursive_mkdir($_SERVER['DOCUMENT_ROOT'] . $path_info['dirname']);
            mkdir(ABS . $path_info['dirname'], 0777, true);
        }

        switch($path_info['ext']) {
            case 'jpeg':
            case 'jpg':
                $this->content_type = 'image/jpeg';
                break;
            case 'gif':
                $this->content_type = 'image/gif';
                break;
            case 'png':
                $this->content_type = 'image/png';
                break;
        }

        if(class_exists('Imagick')){
            $this->image_constrain_imagick(ABS . $this->filename, ABS . $this->resized_filename, $this->resized_width, $this->resized_height);
        }else{
            $this->image_constrain_gd(ABS . $this->filename, ABS . $this->resized_filename, $this->resized_width, $this->resized_height);
        }

        return $this->resized_filename;
    }

    private $allowed_extentions = array('png', 'jpg', 'jpeg', 'gif');

    public function uploadImage($path_info)
    {
        // Имя оригинального файла
        $new_name = $this->object_id . '-' . $this->position . '-' .  Translit::make($path_info['name'], '-') . '.' . $path_info['extension'];

        $base = $path_info['filename'];
        $ext = $path_info['extension'];

        $inner_path = $this->object_id[0].'/'.$this->object_id[1].'/';

        if(in_array(strtolower($ext), $this->allowed_extentions))
        {
            while(file_exists(ABS . '/files/images/originals/' . $this->type . '/'. $inner_path . $new_name))
            {
                $new_base = pathinfo($new_name, PATHINFO_FILENAME);
                if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                    $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
                else
                    $new_name = $base.'_1.'.$ext;
            }

            if (!file_exists(ABS . '/files/images/originals/' . $this->type . '/'. $inner_path))
                mkdir(ABS . '/files/images/originals/' . $this->type . '/' . $inner_path, 0777, true);

            if(copy($this->tmp_file['tmp_name'], ABS . '/files/images/originals/' . $this->type . '/' . $inner_path . $new_name)){
                $this->guarded = [];
                $this->filename = $new_name;
                $this->is_default = false;
                $this->name = '';
                $this->insert();
                return $this;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    public function removeImage()
    {
        if(file_exists(ABS . $this->filename)){
            unlink(ABS . $this->filename);
        }else{
            return false;
        }
    }

    public function download_image($filename)
    {
        // Заливаем только есть такой файл есть в базе
        $this->db->query('SELECT 1 FROM __images WHERE filename=? LIMIT 1', $filename);
        if(!$this->db->result())
            return false;


        // Имя оригинального файла
        $uploaded_file = array_shift(explode('?', pathinfo($filename, PATHINFO_BASENAME)));
        $uploaded_file = array_shift(explode('&', pathinfo($filename, PATHINFO_BASENAME)));
        $base = urldecode(pathinfo($uploaded_file, PATHINFO_FILENAME));
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        // Если такой файл существует, нужно придумать другое название
        $new_name = urldecode($uploaded_file);

        while(file_exists($this->config->root_dir.$this->config->original_images_dir.$new_name))
        {
            $new_base = pathinfo($new_name, PATHINFO_FILENAME);
            if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
            else
                $new_name = $base.'_1.'.$ext;
        }
        $this->db->query('UPDATE __images SET filename=? WHERE filename=?', $new_name, $filename);

        // Перед долгим копированием займем это имя
        fclose(fopen($this->config->root_dir.$this->config->original_images_dir.$new_name, 'w'));
        copy($filename, $this->config->root_dir.$this->config->original_images_dir.$new_name);
        return $new_name;
    }

    public function upload_image($filename, $name, $object)
    {
        // Имя оригинального файла
        $uploaded_file = $new_name = pathinfo($name, PATHINFO_BASENAME);
        $base = pathinfo($uploaded_file, PATHINFO_FILENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        $inner_path = $new_name[0].'/'.$new_name[1].'/';

        if(in_array(strtolower($ext), $this->allowed_extentions))
        {
            while(file_exists($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$new_name))
            {
                $new_base = pathinfo($new_name, PATHINFO_FILENAME);
                if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                    $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
                else
                    $new_name = $base.'_1.'.$ext;
            }
			
            if (!file_exists($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path))
                //$this->recursive_mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path);
                mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path, 0777, true);

            if(move_uploaded_file($filename, $this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$new_name))
                return $new_name;
        }

        return false;
    }

    public function upload_internet_image($url, $name, $object)
    {
        // Имя оригинального файла
        $uploaded_file = $new_name = pathinfo($name, PATHINFO_BASENAME);
        $base = pathinfo($uploaded_file, PATHINFO_FILENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        $inner_path = $new_name[0].'/'.$new_name[1].'/';

        if(in_array(strtolower($ext), $this->allowed_extentions))
        {
            while(file_exists($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$new_name))
            {
                $new_base = pathinfo($new_name, PATHINFO_FILENAME);
                if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                    $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
                else
                    $new_name = $base.'_1.'.$ext;
            }

            if (!file_exists($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path))
                $this->recursive_mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path);
                //mkdir($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path, 0777, true);

            $fi = @file_get_contents($url);
            if ($fi === false)
                return false;

            $fp = @file_put_contents($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$new_name, $fi);
            if ($fp === false)
                return false;

            return $new_name;
        }

        return false;
    }

    /**************
    * object                     - объект для которого добавляется изображение (category, product, etc...)
    * object_id                 - id объекта
    * original_file_name         - оригинальное имя файла
    * temp_file_name            - временное имя файла
    *
    **************/
    public function add_image($object, $object_id, $object_name, $original_file_name, $temp_file_name)
    {
        $this->db->query("SELECT id FROM __images_modules WHERE name=?", $object);
        $module = $this->db->result();
        /*$module = $this->furl->get_system_module_by_name(strval($object));*/
        if ($module === false)
        {
            $this->db->query("INSERT INTO __images_modules(name) VALUES(?)", $object);
            //$module_id = $this->db->insert_id();
            $this->db->query("SELECT id FROM __images_modules WHERE name=?", $object);
            $module = $this->db->result();
        }

        if ($module === false)
            return false;

        $this->db->query("SELECT max(position) as max_pos FROM __images WHERE module_id=? AND object_id=?", $module->id, intval($object_id));
        $max_pos = $this->db->result('max_pos');
        if (!$max_pos)
            $max_pos = 0;
        else
            $max_pos++;

        $uploaded_file = pathinfo($original_file_name, PATHINFO_BASENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        //$original_file_name = mb_strtolower($original_file_name, "utf-8");
        //$original_file_name = preg_replace("/[^a-z0-9-.]/", "", $original_file_name);
        $original_file_name = /*uniqid()*/$object_name . "." . $ext;

        $up = $this->upload_image($temp_file_name, $object_id.$max_pos.'-'.$original_file_name, $object);
        if ($up === false)
            return false;

        $this->db->query("INSERT INTO __images(module_id, name, object_id, filename, position) VALUES(?,?,?,?,?)",
            $module->id,
            '',
            intval($object_id),
            $up,
            $max_pos);

        return true;
    }

    public function add_internet_image($object, $object_id, $object_name, $url)
    {
        $this->db->query("SELECT id FROM __images_modules WHERE name=?", $object);
        $module = $this->db->result();
        /*$module = $this->furl->get_system_module_by_name(strval($object));*/
        if ($module === false)
            return false;

        $this->db->query("SELECT max(position) as max_pos FROM __images WHERE module_id=? AND object_id=?", $module->id, intval($object_id));
        $max_pos = $this->db->result('max_pos');
        if (!isset($max_pos))
            $max_pos = 0;
        else
            $max_pos++;

        $pi = pathinfo($url);

        $original_file_name = $object_id.$max_pos . '-' . /*uniqid()*/$object_name . "." . (isset($pi['extension']) ? $pi['extension'] : 'jpg');

        /*$original_file_name = $object_id.$max_pos.'-internet_image_'.$max_pos;
        if (isset($pi['extension']))
            $original_file_name .= ".".$pi['extension'];*/

        $up = $this->upload_internet_image($url, $original_file_name, $object);
        if ($up === false)
            return false;

        $this->db->query("INSERT INTO __images(module_id, name, object_id, filename, position) VALUES(?,?,?,?,?)",
            $module->id,
            '',
            intval($object_id),
            $up,
            $max_pos);

        return true;
    }

    public function update_image($id, $image)
    {
        $query = $this->db->placehold('UPDATE __images SET ?% WHERE id in (?@)', $image, (array)$id);
        return $this->db->query($query);
    }

    public function delete_image($object, $object_id, $image_id)
    {
        $this->db->query("SELECT id FROM __images_modules WHERE name=?", $object);
        $module = $this->db->result();
        /*$module = $this->furl->get_system_module_by_name(strval($object));*/
        if ($module === false)
            return false;

        $this->db->query("SELECT * FROM __images WHERE module_id=? AND object_id=? AND id=?", $module->id, $object_id, $image_id);
        $img = $this->db->result();
        if (!$img)
            return false;

        $filename = $img->filename;
        $file = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $inner_path = $file[0].'/'.$file[1].'/';

        // Удалить все ресайзы
        $rezised_images = glob($this->config->root_dir.$this->config->resized_images_dir.$object.'/'.$inner_path.$file."*.".$ext);
        if(is_array($rezised_images))
        foreach (glob($this->config->root_dir.$this->config->resized_images_dir.$object.'/'.$inner_path.$file."*.".$ext) as $f)
            @unlink($f);

        @unlink($this->config->root_dir.$this->config->original_images_dir.$object.'/'.$inner_path.$filename);

        $this->db->query("DELETE FROM __images WHERE module_id=? AND object_id=? AND id=?", $module->id, $object_id, $image_id);
        return true;
    }

    function get_images($object, $object_id)
    {
        $this->db->query("SELECT id FROM __images_modules WHERE name=?", $object);
        $module = $this->db->result();
        //$module = $this->furl->get_system_module_by_name(strval($object));
        if ($module === false)
            return false;

        if (empty($object_id))
            return false;

        $this->db->query("SELECT * FROM __images WHERE module_id=? AND object_id=? ORDER BY position", $module->id, $object_id);
        return $this->db->results();
    }

    /**
     * Создание превью средствами gd
     * @param $src_file исходный файл
     * @param $dst_file файл с результатом
     * @param max_w максимальная ширина
     * @param max_h максимальная высота
     * @return bool
     */
    private function image_constrain_gd($src_file, $dst_file, $max_w, $max_h, $watermark=null, $watermark_offet_x=0, $watermark_offet_y=0, $watermark_opacity=1)
    {
        $quality = 80;

        // Параметры исходного изображения
        @list($src_w, $src_h, $src_type) = array_values(getimagesize($src_file));
        $src_type = image_type_to_mime_type($src_type);

        if(empty($src_w) || empty($src_h) || empty($src_type))
            return false;

        // Нужно ли обрезать?
        if (!$watermark && ($src_w <= $max_w) && ($src_h <= $max_h))
        {
            // Нет - просто скопируем файл
            if (!copy($src_file, $dst_file))
                return false;
            return true;
        }

        // Размеры превью при пропорциональном уменьшении
        @list($dst_w, $dst_h) = $this->calc_contrain_size($src_w, $src_h, $max_w, $max_h);

        // Читаем изображение
        switch ($src_type)
        {
            case 'image/jpeg':
                $src_img = imageCreateFromJpeg($src_file);
                break;
            case 'image/gif':
                $src_img = imageCreateFromGif($src_file);
                break;
            case 'image/png':
                $src_img = imageCreateFromPng($src_file);
                imagealphablending($src_img, true);
                break;
            default:
                return false;
        }

        if(empty($src_img))
            return false;

        $src_colors = imagecolorstotal($src_img);

        // create destination image (indexed, if possible)
        if ($src_colors > 0 && $src_colors <= 256)
            $dst_img = imagecreate($dst_w, $dst_h);
        else
            $dst_img = imagecreatetruecolor($dst_w, $dst_h);

        if (empty($dst_img))
            return false;

        $transparent_index = imagecolortransparent($src_img);
        if ($transparent_index >= 0 && $transparent_index <= 128)
        {
            $t_c = imagecolorsforindex($src_img, $transparent_index);
            $transparent_index = imagecolorallocate($dst_img, $t_c['red'], $t_c['green'], $t_c['blue']);
            if ($transparent_index === false)
                return false;
            if (!imagefill($dst_img, 0, 0, $transparent_index))
                return false;
            imagecolortransparent($dst_img, $transparent_index);
        }
        // or preserve alpha transparency for png
        elseif ($src_type === 'image/png')
        {
            if (!imagealphablending($dst_img, false))
                return false;
            $transparency = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
            if (false === $transparency)
                return false;
            if (!imagefill($dst_img, 0, 0, $transparency))
                return false;
            if (!imagesavealpha($dst_img, true))
                return false;
        }

        // resample the image with new sizes
        if (!imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h))
            return false;

        // Watermark
        if(!empty($watermark) && is_readable($watermark) && ($dst_w >= $this->settings->watermark_image_min_width || $dst_h >= $this->settings->watermark_image_min_height))
        {
            $overlay = imagecreatefrompng($watermark);

            // Get the size of overlay
            $owidth = imagesx($overlay);
            $oheight = imagesy($overlay);

            $watermark_x = min(($dst_w-$owidth)*$watermark_offet_x/100, $dst_w);
            $watermark_y = min(($dst_h-$oheight)*$watermark_offet_y/100, $dst_h);

            imagecopy($dst_img, $overlay, $watermark_x, $watermark_y, 0, 0, $owidth, $oheight);
            //imagecopymerge($dst_img, $overlay, $watermark_x, $watermark_y, 0, 0, $owidth, $oheight, $watermark_opacity*100);
        }


        // recalculate quality value for png image
        if ('image/png' === $src_type)
        {
            $quality = round(($quality / 100) * 10);
            if ($quality < 1)
                $quality = 1;
            elseif ($quality > 10)
                $quality = 10;
            $quality = 10 - $quality;
        }

        // Сохраняем изображение
        switch ($src_type)
        {
        case 'image/jpeg':
            return imageJpeg($dst_img, $dst_file, $quality);
        case 'image/gif':
            return imageGif($dst_img, $dst_file, $quality);
        case 'image/png':
            imagesavealpha($dst_img, true);
            return imagePng($dst_img, $dst_file, $quality);
        default:
            return false;
        }
    }

    /**
     * Создание превью средствами imagick
     * @param $src_file исходный файл
     * @param $dst_file файл с результатом
     * @param max_w максимальная ширина
     * @param max_h максимальная высота
     * @return bool
     */
    private function image_constrain_imagick($src_file, $dst_file, $max_w, $max_h, $watermark=null, $watermark_offet_x=0, $watermark_offet_y=0, $watermark_opacity=1, $sharpen=0.2)
    {
        $thumb = new Imagick();

        // Читаем изображение
        if(!$thumb->readImage($src_file))
            return false;

        // Размеры исходного изображения
        $src_w = $thumb->getImageWidth();
        $src_h = $thumb->getImageHeight();

        // Нужно ли обрезать?
        if (!$watermark && ($src_w <= $max_w) && ($src_h <= $max_h))
        {
            // Нет - просто скопируем файл
            if (!copy($src_file, $dst_file))
                return false;
            return true;
        }

        // Размеры превью при пропорциональном уменьшении
        list($dst_w, $dst_h) = $this->calc_contrain_size($src_w, $src_h, $max_w, $max_h);

        // Уменьшаем
        $thumb->thumbnailImage($dst_w, $dst_h);

        // Устанавливаем водяной знак
        if($watermark && is_readable($watermark)  && $dst_w >= 110 && $dst_h >= 110)
        {
            $overlay = new Imagick($watermark);
            //$overlay->setImageOpacity(0.9);
            $overlay->setImageOpacity($watermark_opacity);
            $overlay_compose = $overlay->getImageCompose();

            // Get the size of overlay
            $owidth = $overlay->getImageWidth();
            $oheight = $overlay->getImageHeight();

            $watermark_x = min(($dst_w-$owidth)*$watermark_offet_x/100, $dst_w);
            $watermark_y = min(($dst_h-$oheight)*$watermark_offet_y/100, $dst_h);

        }


        // Анимированные gif требуют прохода по фреймам
        foreach($thumb as $frame)
        {
            // Уменьшаем
            $frame->thumbnailImage($dst_w, $dst_h);

            /* Set the virtual canvas to correct size */
            $frame->setImagePage($dst_w, $dst_h, 0, 0);

            // Наводим резкость
            if($sharpen > 0)
                $thumb->adaptiveSharpenImage($sharpen, $sharpen);

            if(isset($overlay) && is_object($overlay))
            {
                $frame->compositeImage($overlay, $overlay_compose, $watermark_x, $watermark_y, imagick::COLOR_ALPHA);
            }

        }

        // Убираем комменты и т.п. из картинки
        $thumb->stripImage();

        //        $thumb->setImageCompressionQuality(100);

        // Записываем картинку
        if(!$thumb->writeImages($dst_file, true))
            return false;

        // Уборка
        $thumb->destroy();
        if(isset($overlay) && is_object($overlay))
            $overlay->destroy();

        return true;
    }


    /**
     * Вычисляет размеры изображения, до которых нужно его пропорционально уменьшить, чтобы вписать в квадрат $max_w x $max_h
     * @param src_w ширина исходного изображения
     * @param src_h высота исходного изображения
     * @param max_w максимальная ширина
     * @param max_h максимальная высота
     * @return array(w, h)
     */
    function calc_contrain_size($src_w, $src_h, $max_w = 0, $max_h = 0)
    {
        if($src_w == 0 || $src_h == 0)
            return false;

        $dst_w = $src_w;
        $dst_h = $src_h;

        if($src_w > $max_w && $max_w>0)
        {
            $dst_h = $src_h * ($max_w/$src_w);
            $dst_w = $max_w;
        }
        if($dst_h > $max_h && $max_h>0)
        {
            $dst_w = $dst_w * ($max_h/$dst_h);
            $dst_h = $max_h;
        }
        return array($dst_w, $dst_h);
    }

    private function files_identical($fn1, $fn2)
    {
        $buffer_len = 1024;
        if(!$fp1 = fopen($fn1, 'rb'))
            return FALSE;

        if(!$fp2 = fopen($fn2, 'rb')) {
            fclose($fp1);
            return FALSE;
        }

        $same = TRUE;
        while (!feof($fp1) and !feof($fp2))
            if(fread($fp1, $buffer_len) !== fread($fp2, $buffer_len)) {
                $same = FALSE;
                break;
            }

        if(feof($fp1) !== feof($fp2))
            $same = FALSE;

        fclose($fp1);
        fclose($fp2);

        return $same;
    }

    private function removeDir($path)
    {
        if (is_file($path)) {
            @unlink($path);
        }
        else
        {
            $pathes = glob($path . '/*');
            if (!empty($pathes))
            foreach($pathes as $rm_path)
            {
                if (is_file($rm_path))
                    @unlink($rm_path);
                else
                {
                    $this->removeDir($rm_path);
                    @rmdir($rm_path);
                }
            }
        }
    }

    public function clear_cache(){
        //Очистим resized
        if (mb_substr($this->config->resized_images_dir, -1, 1, 'UTF-8') == "/")
            $this->removeDir($this->config->root_dir . mb_substr($this->config->resized_images_dir, 0, mb_strlen($this->config->resized_images_dir, 'UTF-8')-1, 'UTF-8'));
        else
            $this->removeDir($this->config->root_dir . $this->config->resized_images_dir);

        //Пройдемся по всей таблице и удалим изображения, для которых уже не существует объекта к которому они привязаны
        $this->db->query("SELECT i.id, sm.name as module_name, i.object_id, i.filename FROM __images i
            LEFT JOIN __images_modules sm ON i.module_id = sm.id
            ORDER BY i.id");
        $all_images = $this->db->results();
        foreach($all_images as $i)
        {
            //Если такого модуля нет, то удаляем
            if (!isset($i->module_name))
            {
                $filename = $i->filename;
                $file = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $inner_path = $file[0].'/'.$file[1].'/';

                @unlink($this->config->root_dir . $this->config->original_images_dir . $i->module_name . '/' . $inner_path . $filename);

                $this->db->query("DELETE FROM __images WHERE id=?", $i->id);
                continue;
            }

            //Модуль есть, проверим есть ли объект к которому сделана привязка
            switch ($i->module_name){
                case "badges":
                    $object = $this->badges->get_badge($i->object_id);
                    break;
                case "brands":
                    $object = $this->brands->get_brand($i->object_id);
                    break;
                case "categories":
                    $object = $this->categories->get_category($i->object_id);
                    break;
                case "categories-gallery":
                    $object = $this->categories->get_category($i->object_id);
                    break;
                case "materials":
                    $object = $this->materials->get_material($i->object_id);
                    break;
                case "payment":
                    $object = $this->payment->get_payment_method($i->object_id);
                    break;
                case "products":
                    $object = $this->products->get_product($i->object_id);
                    break;
                case "slideshow":
                    $object = $this->slideshow->get_slide($i->object_id);
                    break;
                case "tags":
                    $object = $this->tags->get_tag($i->object_id);
                    break;
                case "reviews":
                    $object = $this->reviews->get_review($i->object_id);
                    break;
            }
            if (!$object)
            {
                $filename = $i->filename;
                $file = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $inner_path = $file[0].'/'.$file[1].'/';

                @unlink($this->config->root_dir . $this->config->original_images_dir . $i->module_name . '/' . $inner_path . $filename);

                $this->db->query("DELETE FROM __images WHERE id=?", $i->id);
                continue;
            }
        }

        //Теперь пройдемся по всем файлам на диске и проверим он чтоб он был в базе, иначе удаляем
        $dirHandleModule = opendir($this->config->root_dir . $this->config->original_images_dir);

        while (false !== ($module_name = readdir($dirHandleModule)))
        {
            if ($module_name == '.' || $module_name == '..')
                continue;
            if (!is_dir($this->config->root_dir . $this->config->original_images_dir . $module_name))
                continue;
            $dirHandleFirstDir = opendir($this->config->root_dir . $this->config->original_images_dir . $module_name);
            while (false !== ($first_dir = readdir($dirHandleFirstDir)))
            {
                if ($first_dir == '.' || $first_dir == '..')
                    continue;
                if (!is_dir($this->config->root_dir . $this->config->original_images_dir . $module_name . "/" . $first_dir))
                    continue;
                $dirHandleSecondDir = opendir($this->config->root_dir . $this->config->original_images_dir . $module_name . "/" . $first_dir);
                while (false !== ($second_dir = readdir($dirHandleSecondDir)))
                {
                    if ($second_dir == '.' || $second_dir == '..')
                        continue;
                    if (!is_dir($this->config->root_dir . $this->config->original_images_dir . $module_name . "/" . $first_dir . "/" . $second_dir))
                        continue;
                    $dirHandleImages = opendir($this->config->root_dir . $this->config->original_images_dir . $module_name . "/" . $first_dir . "/" . $second_dir);
                    while (false !== ($image_file = readdir($dirHandleImages)))
                    {
                        if ($image_file == '.' || $image_file == '..')
                            continue;
                        if (!is_file($this->config->root_dir . $this->config->original_images_dir . $module_name . "/" . $first_dir . "/" . $second_dir . "/" . $image_file))
                            continue;

                        $need_delete = false;

                        $this->db->query("SELECT * FROM __images_modules WHERE name=?", $module_name);
                        $system_module = $this->db->result();
                        if (!$system_module)
                            $need_delete = true;
                        else
                        {
                            $this->db->query("SELECT * FROM __images WHERE module_id=? AND filename=?", $system_module->id, $image_file);
                            $image = $this->db->result();
                            if (!$image)
                                $need_delete = true;
                        }

                        if ($need_delete)
                        {
                            $filename = $image_file;
                            $file = pathinfo($filename, PATHINFO_FILENAME);
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            $inner_path = $file[0].'/'.$file[1].'/';

                            @unlink($this->config->root_dir . $this->config->original_images_dir . $module_name . '/' . $inner_path . $filename);
                        }
                    }
                    closedir($dirHandleImages);
                }
                closedir($dirHandleSecondDir);
            }
            closedir($dirHandleFirstDir);
        }
        closedir($dirHandleModule);
    }
}