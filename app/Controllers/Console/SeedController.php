<?php
namespace App\Controllers\Console;

use App\Controllers\Console\Requests\SeedRequest;
use App\Layers\LayerConsoleController;
use Framework\FileSystem\Folder;
use Framework\Response\ConsoleResponse;
use Framework\Schema\Seed;

class SeedController extends LayerConsoleController
{
    public function fill(SeedRequest $request)
    {
        $file_seeds = $this->getSeedsFromFiles();

        if(isset($file_seeds[$request->getName()])){
            /**
             * @var Seed $seed
             */
            $seed = $file_seeds[$request->getName()];
            $seed->fill();
            return new ConsoleResponse('Seeded ' . $seed->getName());
        }else{
            foreach ($file_seeds as $seed) {
                /**
                 * @var Seed $seed
                 */
                $seed->fill();
                $this->info('Seeded ' . $seed->getName());
            }
            return new ConsoleResponse('Database seeded');
        }
    }

    /**
     * @return array
     */
    public function getSeedsFromFiles()
    {
        $files = Folder::getFiles(ABS . '/database/seeds', 'php');

        $file_seeds = [];
        if($files){
            foreach ($files as $file) {
                //TODO preg_replace
                if($file = str_replace('.php', '', $file)){
                    /** @var Seeder $class */
                    $name = '\\Database\\Seeds\\' . $file;
                    $class = new $name;
                    if($class instanceof Seed){
                        $file_seeds[$class->getName()] = $class;
                    }
                }
            }
        }

        return $file_seeds;
    }
}