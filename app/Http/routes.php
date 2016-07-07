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
Route::get('/anime/search', 'AnimeController@searchEmpty');
Route::post('/anime/search', 'AnimeController@searchPost');

// Anime Details
Route::get('/anime/{show}', 'AnimeController@details');
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}', 'AnimeController@episode');

// Stream Pages
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}/{video}', 'EpisodeController@stream');
Route::get('/stream/{video}', 'EpisodeController@stream');

// Profile Pages
Route::get('/dashboard', 'ProfileController@dashboard');
