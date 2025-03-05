<?php

Route::get('/generic_table/js/{filename}', function($filename){
    $path = dirname(__DIR__);
    $filePath = $path .DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js'.DIRECTORY_SEPARATOR.$filename;
    if(File::exists($filePath)) {
        return response(file_get_contents($filePath))->withHeaders([
             'Content-Type' => 'application/javascript'
        ]);
    }
    return response(status: 404);
})->name('generic_table_js');

Route::get('/generic_table/css/{filename}', function($filename){
    $path = dirname(__DIR__);
    $filePath = $path .DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$filename;
    if(File::exists($filePath)) {
        return response(file_get_contents($filePath))->withHeaders([
             'Content-Type' => 'text/css'
        ]);
    }
    return response(status: 404);
})->name('generic_table_css');