<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::auth();

// Welcome Page
Route::get('/', 'AnimeController@home');

// Information Pages
Route::get('/about', 'PagesController@about');
Route::get('/news', 'PagesController@news');

// Anime Listings
Route::get('/anime/recent', 'AnimeController@recent');
Route::get('/anime/search', 'AnimeController@search');

// Anime Details
Route::get('/anime/{show}', 'ShowController@details');
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}', 'EpisodeController@episode');

// Show Modifications
Route::post('/anime/add', 'ShowController@insert');
Route::post('/anime/update', 'ShowController@update');

// Stream Pages
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}/{mirror}', 'EpisodeController@stream');
Route::get('/stream/{video}', 'EpisodeController@stream');

// Profile Pages
Route::get('/dashboard', 'ProfileController@dashboard');
