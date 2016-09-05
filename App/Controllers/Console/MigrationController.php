<?php
namespace App\Controllers\Console;

use App\Layers\LayerConsoleController;
use Framework\FileSystem\Folder;
use Framework\Request\Types\ConsoleRequest;
use Framework\Response\ConsoleResponse;
use Framework\Schema\Migration;
use Framework\Model\DatabaseModels\Migration as DatabaseMigration;
use DateTime;

class MigrationController extends LayerConsoleController
{
    /**
     * @var Migration
     */
    public $migration;
    /**
     * @var bool
     */
    public $is_migrated = false;

    /**
     *
     */
    public function __construct()
    {
        $this->migration = new Migration();
        $this->migration->up();
        $this->database_migration = new DatabaseMigration();
    }

    /**
     * @param ConsoleRequest $request
     * @return ConsoleResponse
     */
    public function up(ConsoleRequest $request)
    {
        $file_migrations = $this->getMigrationsFromFiles();
        $batch = $this->database_migration->getNextBatch();
        $database_migrations = $this->database_migration->getQuery()
            ->select()
            ->execute()
            ->all('name')
            ->getResult();

        if($file_migrations){
            uasort($file_migrations, function($obj1, $obj2){
                /** @var Migration $obj1 */
                /** @var Migration $obj2 */
                return new DateTime($obj1->getDate()) < new DateTime($obj2->getDate()) ? -1 : 1;
            });
        }

        foreach($file_migrations as $migration){
            /** @var Migration $migration */
            if(!array_key_exists($migration->getName(), $database_migrations ? $database_migrations : [])){
                $migration->up();
                (new DatabaseMigration([
                    'name' => $migration->getName(),
                    'batch' => $batch,
                    'migrated_at' => $migration->getDate()
                ]))->insert();
                $this->is_migrated = true;
                $this->info('Migration up successful: ' . $migration->getName());
            }
        }

        if($this->is_migrated){
            return new ConsoleResponse('All migrations up');
        }else{
            return new ConsoleResponse('Nothing migrate');
        }
    }

    /**
     * @param ConsoleRequest $request
     * @return ConsoleResponse
     */
    public function down(ConsoleRequest $request)
    {
        $file_migrations = $this->getMigrationsFromFiles();
        $batch = $this->database_migration->getLastBatch();
        $database_migrations = $this->database_migration->getByBatch($batch);

        if($file_migrations){
            uasort($file_migrations, function($obj1, $obj2){
                /** @var Migration $obj1 */
                /** @var Migration $obj2 */
                return new DateTime($obj1->getDate()) > new DateTime($obj2->getDate()) ? -1 : 1;
            });
        }

        foreach($file_migrations as $migration){
            /** @var Migration $migration */
            if(array_key_exists($migration->getName(), $database_migrations ? $database_migrations : [])){
                $migration->down();
                (new DatabaseMigration())->getQuery()
                    ->delete()
                    ->where('name = :name AND batch = :batch', [':name' => $migration->getName(), ':batch' => $batch])
                    ->execute();
                $this->is_migrated = true;
                $this->info('Migration down successful: ' . $migration->getName());
            }
        }

        if($this->is_migrated){
            return new ConsoleResponse('All migrations down');
        }else{
            return new ConsoleResponse('Nothing migrate');
        }
    }

    /**
     * @return array
     */
    public function getMigrationsFromFiles()
    {
        $files = Folder::getFiles(ABS . '/database/migrations', 'php');

        $file_migrations = [];
        if($files){
            foreach ($files as $file) {
                //TODO preg_replace
                if($file = str_replace('.php', '', $file)){
                    /** @var Migration $class */
                    $name = '\\Database\\Migrations\\' . $file;
                    $class = new $name;
                    if($class instanceof Migration){
                        $file_migrations[$class->getName()] = $class;
                    }
                }
            }
        }

        return $file_migrations;
    }
}