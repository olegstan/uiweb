<?php
namespace app\models\attachment;

use app\layer\LayerModel;
use app\models\attachment\AttachmentModule;

class Attachment extends LayerModel
{
    protected $table = 'mc_attachments';

    public $module_id;





    function recursive_mkdir($path, $mode = 0777) {
        $path = mb_substr($path, mb_strlen($this->config->root_dir));
        $dirs = explode(DIRECTORY_SEPARATOR , $path);
        $count = count($dirs);
        $path = $this->config->root_dir;
        for ($i = 0; $i < $count; ++$i) {
            if (mb_strlen($dirs[$i], 'utf-8') == 0)
                continue;
            $path .= $dirs[$i] . DIRECTORY_SEPARATOR;
            if (is_dir($path))
                continue;
            if (!mkdir($path, $mode))
                return false;
            if (!chmod($path, $mode))
                return false;
        }
        return true;
    }

    public function upload_attachment($filename, $name, $object)
    {
        // Имя оригинального файла
        $uploaded_file = $new_name = pathinfo($name, PATHINFO_BASENAME);
        $base = pathinfo($uploaded_file, PATHINFO_FILENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        $inner_path = $new_name[0].'/'.$new_name[1].'/';

        while(file_exists($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path.$new_name))
        {
            $new_base = pathinfo($new_name, PATHINFO_FILENAME);
            if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
            else
                $new_name = $base.'_1.'.$ext;
        }

        if (!file_exists($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path))
            $this->recursive_mkdir($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path);

        if(move_uploaded_file($filename, $this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path.$new_name))
            return $new_name;

        return false;
    }

    public function upload_internet_attachment($url, $name, $object)
    {
        // Имя оригинального файла
        $uploaded_file = $new_name = pathinfo($name, PATHINFO_BASENAME);
        $base = pathinfo($uploaded_file, PATHINFO_FILENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        $inner_path = $new_name[0].'/'.$new_name[1].'/';

        while(file_exists($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path.$new_name))
        {
            $new_base = pathinfo($new_name, PATHINFO_FILENAME);
            if(preg_match('/_([0-9]+)$/', $new_base, $parts))
                $new_name = $base.'_'.($parts[1]+1).'.'.$ext;
            else
                $new_name = $base.'_1.'.$ext;
        }

        if (!file_exists($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path))
            $this->recursive_mkdir($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path);

        //$fi = file_get_contents($url);
        $handle = fopen($url, "rb");
        $fi = stream_get_contents($handle);
        fclose($handle);

        if ($fi === false)
            return false;

        $fp = @file_put_contents($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path.$new_name, $fi);
        if ($fp === false)
            return false;

        return $new_name;
    }

    /**************
    * object                     - объект для которого добавляется аттач (category, product, etc...)
    * object_id                 - id объекта
    * original_file_name         - оригинальное имя файла
    * temp_file_name            - временное имя файла
    *
    **************/
    public function add_attachment($object, $object_id, $object_name, $original_file_name, $temp_file_name)
    {
        self::getDB()->query("SELECT id FROM __attachments_modules WHERE name=?", $object);
        $module = self::getDB()->result();
        if ($module === false)
        {
            self::getDB()->query("INSERT INTO __attachments_modules(name) VALUES(?)", $object);
            self::getDB()->query("SELECT id FROM __attachments_modules WHERE name=?", $object);
            $module = self::getDB()->result();
        }

        if ($module === false)
            return false;

        self::getDB()->query("SELECT max(position) as max_pos FROM __attachments WHERE module_id=? AND object_id=?", $module->id, intval($object_id));
        $max_pos = self::getDB()->result('max_pos');
        if (empty($max_pos))
            $max_pos = 1;
        else
            $max_pos++;

        $uploaded_file = pathinfo($original_file_name, PATHINFO_BASENAME);
        $ext = pathinfo($uploaded_file, PATHINFO_EXTENSION);

        $original_file_name = $object_name . "." . $ext;

        $up = $this->upload_attachment($temp_file_name, $object_id.$max_pos.'-'.$original_file_name, $object);
        if ($up === false)
            return false;

        self::getDB()->query("INSERT INTO __attachments(module_id, name, extension, object_id, filename, position) VALUES(?,?,?,?,?,?)",
            $module->id,
            '',
            $ext,
            intval($object_id),
            $up,
            $max_pos);

        return true;
    }

    public function add_internet_attachment($object, $object_id, $object_name, $url)
    {
        self::getDB()->query("SELECT id FROM __attachments_modules WHERE name=?", $object);
        $module = self::getDB()->result();
        if ($module === false)
            return false;

        self::getDB()->query("SELECT max(position) as max_pos FROM __attachments WHERE module_id=? AND object_id=?", $module->id, intval($object_id));
        $max_pos = self::getDB()->result('max_pos');
        if (!isset($max_pos))
            $max_pos = 0;
        else
            $max_pos++;

        $pi = pathinfo($url);

        $original_file_name = $object_id.$max_pos . '-' . $object_name . "." . (isset($pi['extension']) ? $pi['extension'] : '');

        $up = $this->upload_internet_attachment($url, $original_file_name, $object);
        if ($up === false)
            return false;

        self::getDB()->query("INSERT INTO __attachments(module_id, name, extension, object_id, filename, position) VALUES(?,?,?,?,?,?)",
            $module->id,
            '',
            isset($pi['extension']) ? $pi['extension'] : '',
            intval($object_id),
            $up,
            $max_pos);

        return true;
    }

    public function update_attachment($id, $attachment)
    {
        $query = self::getDB()->placehold('UPDATE __attachments SET ?% WHERE id in (?@)', $attachment, (array)$id);
        return self::getDB()->query($query);
    }

    public function delete_attachment($object, $object_id, $attachment_id)
    {
        self::getDB()->query("SELECT id FROM __attachments_modules WHERE name=?", $object);
        $module = self::getDB()->result();
        if ($module === false)
            return false;

        self::getDB()->query("SELECT * FROM __attachments WHERE module_id=? AND object_id=? AND id=?", $module->id, $object_id, $attachment_id);
        $img = self::getDB()->result();
        if (!$img)
            return false;

        $filename = $img->filename;
        $file = pathinfo($filename, PATHINFO_FILENAME);
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $inner_path = $file[0].'/'.$file[1].'/';

        @unlink($this->config->root_dir.$this->config->attachments_dir.$object.'/'.$inner_path.$filename);

        self::getDB()->query("DELETE FROM __attachments WHERE module_id=? AND object_id=? AND id=?", $module->id, $object_id, $attachment_id);
        return true;
    }

    function get_attachments($object, $object_id)
    {
        self::getDB()->query("SELECT id FROM __attachments_modules WHERE name=?", $object);
        $module = self::getDB()->result();
        if ($module === false)
            return false;

        if (empty($object_id))
            return false;

        self::getDB()->query("SELECT * FROM __attachments WHERE module_id=? AND object_id=? ORDER BY position", $module->id, $object_id);
        return self::getDB()->results();
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
        //Пройдемся по всей таблице и удалим изображения, для которых уже не существует объекта к которому они привязаны
        self::getDB()->query("SELECT a.id, am.name as module_name, a.object_id, a.filename FROM __attachments a
            LEFT JOIN __attachments_modules am ON a.module_id = am.id
            ORDER BY a.id");
        $all_attachments = self::getDB()->results();
        foreach($all_attachments as $i)
        {
            //Если такого модуля нет, то удаляем
            if (!isset($i->module_name))
            {
                $filename = $i->filename;
                $file = pathinfo($filename, PATHINFO_FILENAME);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $inner_path = $file[0].'/'.$file[1].'/';

                @unlink($this->config->root_dir . $this->config->attachments_dir . $i->module_name . '/' . $inner_path . $filename);

                self::getDB()->query("DELETE FROM __attachments WHERE id=?", $i->id);
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
                case "materials-gallery":
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

                @unlink($this->config->root_dir . $this->config->attachments_dir . $i->module_name . '/' . $inner_path . $filename);

                self::getDB()->query("DELETE FROM __attachments WHERE id=?", $i->id);
                continue;
            }
        }

        //Теперь пройдемся по всем файлам на диске и проверим он чтоб он был в базе, иначе удаляем
        $dirHandleModule = opendir($this->config->root_dir . $this->config->attachments_dir);

        while (false !== ($module_name = readdir($dirHandleModule)))
        {
            if ($module_name == '.' || $module_name == '..')
                continue;
            if (!is_dir($this->config->root_dir . $this->config->attachments_dir . $module_name))
                continue;
            $dirHandleFirstDir = opendir($this->config->root_dir . $this->config->attachments_dir . $module_name);
            while (false !== ($first_dir = readdir($dirHandleFirstDir)))
            {
                if ($first_dir == '.' || $first_dir == '..')
                    continue;
                if (!is_dir($this->config->root_dir . $this->config->attachments_dir . $module_name . "/" . $first_dir))
                    continue;
                $dirHandleSecondDir = opendir($this->config->root_dir . $this->config->attachments_dir . $module_name . "/" . $first_dir);
                while (false !== ($second_dir = readdir($dirHandleSecondDir)))
                {
                    if ($second_dir == '.' || $second_dir == '..')
                        continue;
                    if (!is_dir($this->config->root_dir . $this->config->attachments_dir . $module_name . "/" . $first_dir . "/" . $second_dir))
                        continue;
                    $dirHandleImages = opendir($this->config->root_dir . $this->config->attachments_dir . $module_name . "/" . $first_dir . "/" . $second_dir);
                    while (false !== ($image_file = readdir($dirHandleImages)))
                    {
                        if ($image_file == '.' || $image_file == '..')
                            continue;
                        if (!is_file($this->config->root_dir . $this->config->attachments_dir . $module_name . "/" . $first_dir . "/" . $second_dir . "/" . $image_file))
                            continue;

                        $need_delete = false;

                        self::getDB()->query("SELECT * FROM __attachments_modules WHERE name=?", $module_name);
                        $system_module = self::getDB()->result();
                        if (!$system_module)
                            $need_delete = true;
                        else
                        {
                            self::getDB()->query("SELECT * FROM __attachments WHERE module_id=? AND filename=?", $system_module->id, $image_file);
                            $image = self::getDB()->result();
                            if (!$image)
                                $need_delete = true;
                        }

                        if ($need_delete)
                        {
                            $filename = $image_file;
                            $file = pathinfo($filename, PATHINFO_FILENAME);
                            $ext = pathinfo($filename, PATHINFO_EXTENSION);
                            $inner_path = $file[0].'/'.$file[1].'/';

                            @unlink($this->config->root_dir . $this->config->attachments_dir . $module_name . '/' . $inner_path . $filename);
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

    /**
     * @param $name
     * @param $object_id
     * @return bool
     *
     *
     *
     * ************************* new
     */

}