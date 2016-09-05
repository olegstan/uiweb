<?php
namespace Framework\Resize;

class ResizeGd
{
    public function resize($original, $resized, $max_w, $max_h, $watermark = null, $watermark_offet_x = 0, $watermark_offet_y = 0, $watermark_opacity = 1)
    {
        $quality = 80;

        // Параметры исходного изображения
        @list($src_w, $src_h, $src_type) = array_values(getimagesize($original));
        $src_type = image_type_to_mime_type($src_type);

        if(empty($src_w) || empty($src_h) || empty($src_type))
            return false;



        // Нужно ли обрезать?
        if (!$watermark && ($src_w <= $max_w) && ($src_h <= $max_h))
        {
            // Нет - просто скопируем файл
            if (!copy($original, $resized))
                return false;
            return true;
        }

        // Размеры превью при пропорциональном уменьшении
        list($dst_w, $dst_h) = $this->calc_contrain_size($src_w, $src_h, $max_w, $max_h);

        // Читаем изображение
        switch ($src_type)
        {
            case 'image/jpeg':
                $src_img = imageCreateFromJpeg($original);
                break;
            case 'image/gif':
                $src_img = imageCreateFromGif($original);
                break;
            case 'image/png':
                $src_img = imageCreateFromPng($original);
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
                return imageJpeg($dst_img, $resized, $quality);
            case 'image/gif':
                return imageGif($dst_img, $resized, $quality);
            case 'image/png':
                imagesavealpha($dst_img, true);
                return imagePng($dst_img, $resized, $quality);
            default:
                return false;
        }
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
}