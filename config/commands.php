<?php
use Framework\Route\Route;

//php index.php migration_up
Route::console('migration_up', 'migration_up', \App\Controllers\Console\MigrationController::class, 'up');
Route::console('migration_down', 'migration_down', \App\Controllers\Console\MigrationController::class, 'down');

//php index.php generate type=migration name=create_countries_table
//php index.php generate type=seed name=fill_countries_table
Route::console('generate', 'generate', \App\Controllers\Console\GeneratorController::class, 'migration');

//php index.php seed name=fill_countries_table
Route::console('seed', 'seed', \App\Controllers\Console\SeedController::class, 'fill');

Route::console('config', 'config', \App\Controllers\Console\ConfigController::class, 'sheet');