<?php
namespace core;

class AssetBundle
{
    public $headerCSS = [];
    public $headerJS = [];
    public $footerCSS = [];
    public $footerJS = [];

    public function __construct()
    {

    }
    
    public function addHeaderCss($path)
    {
        $this->headerCSS[$path] = $path;
    }

    public function addHeaderJS($path)
    {
        $this->headerJS[$path] = $path;
    }

    public function addFooterCSS($path)
    {
        $this->footerCSS[$path] = $path;
    }

    public function addFooterJS($path)
    {
        $this->footerJS[$path] = $path;
    }

    public function headerCSS()
    {
        if ($this->headerCSS) {
            $result = '';
            foreach ($this->headerCSS as $file) {
                $result .= '<link rel="stylesheet" property="stylesheet" type="text/css" href="' . $file . '">' . "\n";
            }
            return $result;
        }
    }

    public function addAsset($array_name, $path)
    {
        array_push($this->$array_name, $path);
    }

    public function removeAsset($array_name, $path)
    {
        $key = array_search($path, $this->$array_name);
        if ($key !== false) {
            unset($this->$array_name[$key]);
        }
    }

    public function headerJS()
    {
        if ($this->headerJS) {
            $result = '';
            foreach ($this->headerJS as $file) {
                $result .= '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
            }
            return $result;
        }
    }

    public function footerCSS()
    {
        if ($this->footerCSS) {
            $result = '';
            foreach ($this->footerCSS as $file) {
                $result .= '<link rel="stylesheet" property="stylesheet" type="text/css" href="' . $file . '">' . "\n";
            }
            return $result;
        }
    }

    public function footerJS()
    {
        if ($this->footerJS){
            $result = '';
            foreach ($this->footerJS as $file) {
                $result .= '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
            }
            return $result;
        }
    }
}