<?php

use App\Patterns\Pattern;
use App\Patterns\PatternRenderer;
use Illuminate\Foundation\Inspiring;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('generate {path}', function () {
    /** @var \Illuminate\Console\Command $this */
    $path = rtrim($this->argument('path'), '/');
    $newPath = base_path($path . '-generated/');
    if (!File::exists($newPath)) {
        File::makeDirectory($newPath);
    }

    foreach (File::files($path) as $file) {
        $filePath = base_path($path . '/' . $file->getRelativePathname());
        if (!in_array($file->getExtension(), ['png', 'jpg'])) {
            $this->info('Skipping: ' . $filePath);
            continue;
        }
        $this->getOutput()->write('Generating: ' . $filePath);
        $img = \Image::make($filePath);
        $pattern = new Pattern($img);
        $renderer = new PatternRenderer($pattern, 26, $file->getRelativePathname());

        $renderer->render()->save($newPath . $file->getRelativePathname());
        $this->info(' ✔');
    }

    $this->info('Done...');
})->describe('Generate all images from folder');
