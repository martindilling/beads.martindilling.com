<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Patterns\Pattern;
use App\Patterns\PatternRenderer;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::post('generate', function (\Illuminate\Http\Request $request) {
    $uploadedFile = $request->file('image');
    $uuid = Str::uuid();
    $originalName = $uuid . '_original.' . $uploadedFile->getClientOriginalExtension();
    $patternName = $uuid . '_pattern.png';
    if (!$uploadedFile->isValid()) {
        return 'Error';
    }
    $original = $uploadedFile->storeAs('/', $originalName, 'public');

    $img = \Image::make(public_path('storage/' . $original));
    $pattern = new Pattern($img);
    $renderer = new PatternRenderer($pattern, 26, $uploadedFile->getClientOriginalName());
    $renderer->render()->save(public_path('storage/' . $patternName));

    return redirect()->route('show', ['uuid' => $uuid]);
})->name('generate');


Route::get('show/{uuid}', function (string $uuid) {
    return view('show', ['image' => asset('storage/' . $uuid . '_pattern.png')]);
})->name('show');
