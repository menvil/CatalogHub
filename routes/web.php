<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/health.php';

Route::get('/', function () {
    return view('pages.home');
});
