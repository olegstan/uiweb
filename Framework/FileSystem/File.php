<?php
namespace Framework\FileSystem;

use Framework\FileSystem\Exceptions\NotExistsExeption;
use Framework\FileSystem\Exceptions\NotWritableException;

class File extends FileSystem
{
    /**
     * @var string|null
     */
    public $path;
    /**
     * @var string
     */
    public $filename;
    /**
     * @var string
     */
    public $extension;
    /**
     * @var string
     */
    public $dirname;

    /**
     * @var integer
     */
    public $size;
//    /**
//     * @var string
//     * from_url
//     * from_path
//     * from_post
//     * from_tmp
//     */

    /**
     * @var string
     */
    public $type;

    public function __construct($path = null)
    {
        $this->path = $path;

        if($this->path){
            $path_info = pathinfo($this->path);
            $this->filename = $path_info['filename'];
            $this->extension = $path_info['extension'];
            $this->dirname = $path_info['dirname'];
        }
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getDirname()
    {
        return $this->dirname;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    public function getSize()
    {
        if(!isset($this->size)){
            $this->size = filesize($this->getPath());
        }
        return $this->size;
    }

    public function getType()
    {
        if(!isset($this->type)){
            $this->type = mime_content_type($this->getPath());
        }
        return $this->type;

    }

    /**
     * @param $path
     * @return bool
     * @throws NotExistsExeption
     * @throws NotWritableException
     */
    public function move($path)
    {
        if(!$this->isExists($path)){
            throw new NotExistsExeption($path);
        }

        if(!$this->isWritable($path)){
            throw new NotWritableException($path);
        }

        if(copy($this->getPath(), $path)){
            $this->path = $path;
            return true;
        }else{
            return false;
        }
    }

//    /**
//     * @param $path
//     */
//
//    public static function read($path)
//    {
//        $handle = fopen($path, "r");
//        if ($handle) {
//            while (($line = fgets($handle)) !== false) {
//                $lines[] = $line;
//            }
//            fclose($handle);
//
//            return $lines;
//        } else {
//            return;
//        }
//    }
//
//    public static function get($path)
//    {
//
//    }
//
//    public static function save($file_name, $path)
//    {
//
//    }
//
//    public static function saveAsString($string, $file_name, $path)
//    {
//        file_put_contents($path . $file_name, $string);
//    }
//
//    public static function remove()
//    {
//
//    }
//
//    public static function download($path)
//    {
//        if (file_exists($path)) {
//            header('Content-Description: File Transfer');
//            header('Content-Type: application/octet-stream');
//            header('Content-Disposition: attachment; filename='.basename($path));
//            header('Expires: 0');
//            header('Cache-Control: must-revalidate');
//            header('Pragma: public');
//            header('Content-Length: ' . filesize($path));
//            readfile($path);
//            die();
//        }
//    }
//
//    public static function upload()
//    {
//
//    }
//
//    /**
//     * Determine if a file exists.
//     *
//     * @param  string  $path
//     * @return bool
//     */
//    public function exists($path)
//    {
//        return file_exists($path);
//    }
//
////    public function get($path)
////    {
////        if ($this->isFile($path)) {
////            return file_get_contents($path);
////        }
////
////        throw new FileNotFoundException("File does not exist at path {$path}");
////    }
//
//    public function getRequire($path)
//    {
//        if ($this->isFile($path)) {
//            return require $path;
//        }
//
//        throw new FileNotFoundException("File does not exist at path {$path}");
//    }
//
//    /**
//     * Require the given file once.
//     *
//     * @param  string  $file
//     * @return mixed
//     */
//    public function requireOnce($file)
//    {
//        require_once $file;
//    }
//
//    /**
//     * Write the contents of a file.
//     *
//     * @param  string  $path
//     * @param  string  $contents
//     * @param  bool  $lock
//     * @return int
//     */
//    public function put($path, $contents, $lock = false)
//    {
//        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
//    }
//
//    /**
//     * Prepend to a file.
//     *
//     * @param  string  $path
//     * @param  string  $data
//     * @return int
//     */
//    public function prepend($path, $data)
//    {
//        if ($this->exists($path)) {
//            return $this->put($path, $data.$this->get($path));
//        }
//
//        return $this->put($path, $data);
//    }
//
//    /**
//     * Append to a file.
//     *
//     * @param  string  $path
//     * @param  string  $data
//     * @return int
//     */
//    public function append($path, $data)
//    {
//        return file_put_contents($path, $data, FILE_APPEND);
//    }
//
//    /**
//     * Delete the file at a given path.
//     *
//     * @param  string|array  $paths
//     * @return bool
//     */
//    public function delete($paths)
//    {
//        $paths = is_array($paths) ? $paths : func_get_args();
//
//        $success = true;
//
//        foreach ($paths as $path) {
//            try {
//                if (! @unlink($path)) {
//                    $success = false;
//                }
//            } catch (ErrorException $e) {
//                $success = false;
//            }
//        }
//
//        return $success;
//    }
//
//    /**
//     * Move a file to a new location.
//     *
//     * @param  string  $path
//     * @param  string  $target
//     * @return bool
//     */
//    public function move($path, $target)
//    {
//        return rename($path, $target);
//    }
//
//    /**
//     * Copy a file to a new location.
//     *
//     * @param  string  $path
//     * @param  string  $target
//     * @return bool
//     */
//    public function copy($path, $target)
//    {
//        return copy($path, $target);
//    }
//
//    /**
//     * Extract the file name from a file path.
//     *
//     * @param  string  $path
//     * @return string
//     */
//    public function name($path)
//    {
//        return pathinfo($path, PATHINFO_FILENAME);
//    }
//
//    /**
//     * Extract the file extension from a file path.
//     *
//     * @param  string  $path
//     * @return string
//     */
//    public function extension($path)
//    {
//        return pathinfo($path, PATHINFO_EXTENSION);
//    }
//
//    /**
//     * Get the file type of a given file.
//     *
//     * @param  string  $path
//     * @return string
//     */
//    public function type($path)
//    {
//        return filetype($path);
//    }
//
//    /**
//     * Get the mime-type of a given file.
//     *
//     * @param  string  $path
//     * @return string|false
//     */
//    public function mimeType($path)
//    {
//        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
//    }
//
//    /**
//     * Get the file size of a given file.
//     *
//     * @param  string  $path
//     * @return int
//     */
//    public function size($path)
//    {
//        return filesize($path);
//    }
//
//    /**
//     * Get the file's last modification time.
//     *
//     * @param  string  $path
//     * @return int
//     */
//    public function lastModified($path)
//    {
//        return filemtime($path);
//    }
//
//    /**
//     * Determine if the given path is a directory.
//     *
//     * @param  string  $directory
//     * @return bool
//     */
//    public function isDirectory($directory)
//    {
//        return is_dir($directory);
//    }
//
//    /**
//     * Determine if the given path is writable.
//     *
//     * @param  string  $path
//     * @return bool
//     */
//    public function isWritable($path)
//    {
//        return is_writable($path);
//    }
//
//    /**
//     * Determine if the given path is a file.
//     *
//     * @param  string  $file
//     * @return bool
//     */
//    public function isFile($file)
//    {
//        return is_file($file);
//    }
//
//    /**
//     * Find path names matching a given pattern.
//     *
//     * @param  string  $pattern
//     * @param  int     $flags
//     * @return array
//     */
//    public function glob($pattern, $flags = 0)
//    {
//        return glob($pattern, $flags);
//    }
//
//    /**
//     * Get an array of all files in a directory.
//     *
//     * @param  string  $directory
//     * @return array
//     */
//    public function files($directory)
//    {
//        $glob = glob($directory.'/*');
//
//        if ($glob === false) {
//            return [];
//        }
//
//        // To get the appropriate files, we'll simply glob the directory and filter
//        // out any "files" that are not truly files so we do not end up with any
//        // directories in our list, but only true files within the directory.
//        return array_filter($glob, function ($file) {
//            return filetype($file) == 'file';
//        });
//    }
//
//    /**
//     * Get all of the files from the given directory (recursive).
//     *
//     * @param  string  $directory
//     * @return array
//     */
//    public function allFiles($directory)
//    {
//        return iterator_to_array(Finder::create()->files()->in($directory), false);
//    }
//
//    /**
//     * Get all of the directories within a given directory.
//     *
//     * @param  string  $directory
//     * @return array
//     */
//    public function directories($directory)
//    {
//        $directories = [];
//
//        foreach (Finder::create()->in($directory)->directories()->depth(0) as $dir) {
//            $directories[] = $dir->getPathname();
//        }
//
//        return $directories;
//    }
//
//    /**
//     * Create a directory.
//     *
//     * @param  string  $path
//     * @param  int     $mode
//     * @param  bool    $recursive
//     * @param  bool    $force
//     * @return bool
//     */
//    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
//    {
//        if ($force) {
//            return @mkdir($path, $mode, $recursive);
//        }
//
//        return mkdir($path, $mode, $recursive);
//    }
//
//    /**
//     * Copy a directory from one location to another.
//     *
//     * @param  string  $directory
//     * @param  string  $destination
//     * @param  int     $options
//     * @return bool
//     */
//    public function copyDirectory($directory, $destination, $options = null)
//    {
//        if (! $this->isDirectory($directory)) {
//            return false;
//        }
//
//        $options = $options ?: FilesystemIterator::SKIP_DOTS;
//
//        // If the destination directory does not actually exist, we will go ahead and
//        // create it recursively, which just gets the destination prepared to copy
//        // the files over. Once we make the directory we'll proceed the copying.
//        if (! $this->isDirectory($destination)) {
//            $this->makeDirectory($destination, 0777, true);
//        }
//
//        $items = new FilesystemIterator($directory, $options);
//
//        foreach ($items as $item) {
//            // As we spin through items, we will check to see if the current file is actually
//            // a directory or a file. When it is actually a directory we will need to call
//            // back into this function recursively to keep copying these nested folders.
//            $target = $destination.'/'.$item->getBasename();
//
//            if ($item->isDir()) {
//                $path = $item->getPathname();
//
//                if (! $this->copyDirectory($path, $target, $options)) {
//                    return false;
//                }
//            }
//
//            // If the current items is just a regular file, we will just copy this to the new
//            // location and keep looping. If for some reason the copy fails we'll bail out
//            // and return false, so the developer is aware that the copy process failed.
//            else {
//                if (! $this->copy($item->getPathname(), $target)) {
//                    return false;
//                }
//            }
//        }
//
//        return true;
//    }
//
//    /**
//     * Recursively delete a directory.
//     *
//     * The directory itself may be optionally preserved.
//     *
//     * @param  string  $directory
//     * @param  bool    $preserve
//     * @return bool
//     */
//    public function deleteDirectory($directory, $preserve = false)
//    {
//        if (! $this->isDirectory($directory)) {
//            return false;
//        }
//
//        $items = new FilesystemIterator($directory);
//
//        foreach ($items as $item) {
//            // If the item is a directory, we can just recurse into the function and
//            // delete that sub-directory otherwise we'll just delete the file and
//            // keep iterating through each file until the directory is cleaned.
//            if ($item->isDir() && ! $item->isLink()) {
//                $this->deleteDirectory($item->getPathname());
//            }
//
//            // If the item is just a file, we can go ahead and delete it since we're
//            // just looping through and waxing all of the files in this directory
//            // and calling directories recursively, so we delete the real path.
//            else {
//                $this->delete($item->getPathname());
//            }
//        }
//
//        if (! $preserve) {
//            @rmdir($directory);
//        }
//
//        return true;
//    }
//
//    /**
//     * Empty the specified directory of all files and folders.
//     *
//     * @param  string  $directory
//     * @return bool
//     */
//    public function cleanDirectory($directory)
//    {
//        return $this->deleteDirectory($directory, true);
//    }
}