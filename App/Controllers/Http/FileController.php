<?php
namespace App\Controllers\Http;

use App\Controllers\Http\Requests\FileRequest;
use Framework\Controller\HttpController;
use Framework\FileSystem\Folder;
use Framework\FileSystem\Image;
use Framework\Response\Types\ImageResponse;

class FileController extends HttpController
{
    public function images(FileRequest $request)
    {
        $image = new Image($request->getUriWithAbsolutePath());

        $image->resize($request->getPath(), $image->getPath(), $request->getWidth(), $request->getHeight());

        return new ImageResponse($image);
    }
}