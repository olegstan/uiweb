<?php
namespace Framework\Resize;

class ResizeImagick
{
    public function resize($src_file, $dst_file, $max_w, $max_h, $watermark=null, $watermark_offet_x=0, $watermark_offet_y=0, $watermark_opacity=1, $sharpen=0.2)
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
}