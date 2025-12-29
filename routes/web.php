<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/game', function () {
    return view('game');
});

Route::get('/tetris', function () {
    return view('tetris');
});

Route::get('/rpg', function () {
    return view('rpg');
});

Route::get('/plinko', function () {
    return view('plinko');
});

Route::get('/pinball', function () {
    return view('pinball');
});
