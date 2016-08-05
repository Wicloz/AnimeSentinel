<?php

//php artisan queue:listen --queue=periodic_high,periodic_low,default --timeout=0
//dispatch((new \App\Jobs\FindRecentVideos)->onQueue('periodic_low'));

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
Route::get('/about', 'PagesController@about'); //TODO
Route::get('/news', 'PagesController@news'); //TODO

// Streamers Pages
Route::get('/streamers', 'StreamersController@list'); //TODO
Route::get('/streamers/{streamer}', 'StreamersController@details'); //TODO

// Anime Listings
Route::get('/anime', 'AnimeController@list');
Route::get('/anime/recent', 'AnimeController@recent');
Route::get('/anime/recent/list', 'AnimeController@recentList');
Route::get('/anime/recent/grid', 'AnimeController@recentGrid');
Route::get('/anime/search', 'AnimeController@search');

// Anime Details
Route::get('/anime/{show}', 'ShowController@details');
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}', 'EpisodeController@episode');

// Show Modifications
Route::post('/anime/add', 'ShowController@insert');

// Stream Pages
Route::get('/anime/{show}/{translation_type}/episode-{episode_num}/{streamer}/{mirror}', 'EpisodeController@stream2');
Route::get('/stream/{video}', 'EpisodeController@stream');
Route::get('/stream/{video}/video', 'EpisodeController@static');

// Profile Pages (TODO)
Route::get('/dashboard', 'ProfileController@dashboard');
