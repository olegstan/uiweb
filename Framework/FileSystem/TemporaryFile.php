<?php
namespace Framework\FileSystem;

class TemporaryFile extends File
{
    /**
     * @var
     */
    public $error;

    public function __construct(array $attributes)
    {
        $this->path = $attributes['tmp_name'];
        $this->filename = $attributes['name'];
        $this->size = $attributes['size'];
        $this->type = $attributes['type'];
        $this->error = $attributes['error'];
    }
}