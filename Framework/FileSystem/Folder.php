<?php
namespace Framework\FileSystem;

use Framework\FileSystem\FileSystem;

class Folder extends FileSystem
{
    /**
     * @param $path
     * @param string $extenshion
     * @param array $files
     * @return array
     * TODO return array Files
     */
    public static function getFiles($path, $extenshion = '', $files = [])
    {
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry !== '.' && $entry !== '..') {
                    if($extenshion){
                        if(preg_match('#^(.*)\.' . $extenshion . '$#', $entry)){
                            $files[] = $entry;
                        }
                    }else{
                        $files[] = $entry;
                    }
                }
            }
            closedir($handle);
        }
        return $files;
    }

    public static function getFolders($path)
    {

    }

    public static function getAll($path)
    {

    }

    public static function create($path, $mode = 0777, $recursive = true)
    {
        if(!file_exists($path)){
            mkdir($path, $mode, $recursive);
        }
    }
}