<?php
namespace Framework\View;

use Framework\Auth\Auth;
use Framework\Route\Exceptions\NotFoundRouteException;
use Framework\View\Exceptions\ContentKeyIssetException;
use Framework\View\Exceptions\NotFoundViewException;

class View
{
//    public function getDescription()
//    {
//        return '<meta name="description" content="' . $this->core->description . '">';
//    }
//
//    public function getKeywords()
//    {
//        return '<meta name="keywords" content="' . $this->core->keywords . '">';
//    }
//
//    public function getTitle()
//    {
//        return '<title>' . $this->core->title . '</title>';
//    }
//
//    public function getAlternate()
//    {
//        $languages = $this->core->languages;
//        if (($key = array_search($this->core->language, $languages)) !== false) {
//            unset($languages[$key]);
//        }
//
//        $result = '';
//        foreach ($languages as $language) {
//            $result .= '<link rel="alternate" hreflang="' . $language . '" href="http://uiweb.ru/en" />' . "\n";
//        }
//        return $result;
//    }

    protected $base_path = '/resources/views/';

    /**
     * @var string
     */
    public $view_path;
    /**
     * @var string
     */
    public $view_content;
    /**
     * @var
     */
    public $layout_path;
    /**
     * @var Url
     */
    public $url;
    /**
     * @var string
     */
    public $page;
    /**
     * @var array
     */
    public $contents = [];
    /**
     * @var string
     */
    public $last_content_key;

    /**
     * @param $path
     * @param array $data
     */
    public function __construct($path, array $data = [])
    {
        $this->page = $this->setViewContent($path, $data)->getLayoutContent($data);
    }

    /**
     * @return Url
     */
    public function getUrl()
    {
        return new Url();
    }

    /**
     * @return Modificator
     */
    public function getModificator()
    {
        return new Modificator();
    }

    /**
     * @return Auth
     */
    public function getAuth()
    {
        return Auth::getInstance();
    }

    /**
     * @return string
     */
    public function getViewContent()
    {
        return $this->view_content;
    }

    /**
     * @param $path
     * @param array $data
     * @return $this
     */
    public function setViewContent($path, array $data = [])
    {
        $this->view_content = $this->render($path, $data);
        return $this;
    }

    /**
     * @param $path
     * @param array $data
     * @return string
     */
    public function render($path, array $data = [])
    {
        try {
            ob_start();
            $path = ABS . $this->base_path . $path;

            if (!empty($data)) {
                extract($data);
            }

            //TODO is file another exception
            if (file_exists($path) && is_file($path)) {
                require($path);
            } else {
                throw new NotFoundViewException($path);
            }

            return ob_get_clean();
        }catch (NotFoundViewException $e){
            echo $e->getMessage();
        }
    }

    public function startBuffer($key)
    {
        if(isset($this->contents[$key])){
            throw new ContentKeyIssetException($key);
        }
        $this->last_content_key = $key;
        ob_start();
    }

    public function endBuffer()
    {
        $this->contents[$this->last_content_key] = ob_get_clean();
    }

    public function flushBuffer($key)
    {
        if(isset($this->contents[$key])){
            return $this->contents[$key];
        }else{
            return;
        }
    }

    public function getLayoutContent(array $data = [])
    {
        return $this->render($this->getLayoutPath(), $data);
    }

    public function setLayoutPath($path)
    {
        $this->layout_path = $path;
    }

    public function getLayoutPath()
    {
        return $this->layout_path;
    }

    public function __toString()
    {
        return $this->page;
    }
}