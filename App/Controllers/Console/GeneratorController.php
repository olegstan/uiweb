<?php
namespace App\Controllers\Console;

use App\Controllers\Console\Requests\GeneratorRequest;
use Framework\Controller\ConsoleController;
use Framework\Generator\Generator;
use Framework\Response\ConsoleResponse;

class GeneratorController extends ConsoleController
{
    //TODO required param, exists file
    public function migration(GeneratorRequest $request)
    {
        (new Generator($request->getTemplateType(), ['name' => $request->getName()]))->generate();

        return new ConsoleResponse('Success created ' . $request->getTemplateType() . ' ' . $request->getName());
    }
}