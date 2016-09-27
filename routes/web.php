<?php

\App\AnimeSentinel\Actions\FindVideos::findVideosForShow(\App\Show::withTitle('Mob Psycho 100')->first());

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Auth::routes();

// Welcome Page
Route::get('/', 'AnimeController@home');

// Information Pages
Route::get('/about', 'PagesController@about');
Route::get('/about/mal', 'PagesController@about_mal'); //TODO
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
Route::get('/anime/{show}/{title?}', 'ShowController@details');

// Stream Pages
Route::get('/anime/{show}/{title}/{translation_type}/episode-{episode_num}', 'EpisodeController@gotoEpisode');
Route::get('/anime/{show}/{title}/{translation_type}/episode-{episode_num}/{streamer}/{mirror}', 'EpisodeController@episode');
Route::get('/stream/{video}/video', 'EpisodeController@static');

// Post Controller
Route::post('/anime/add', 'PostController@showAdd');

// Profile Pages
Route::get('/dashboard', 'UserController@overview'); //TODO
Route::get('/user/settings', 'UserController@settings_general');
Route::get('/user/notifications/mail', 'UserController@settings_nots_mail');

// Profile Modifications
Route::post('/user/settings/general', 'Auth\UpdateSettingsController@general');
Route::post('/user/settings/mal', 'Auth\UpdateSettingsController@mal');
Route::post('/user/settings/password', 'Auth\UpdateSettingsController@password');
Route::post('/user/notifications/mail/general', 'Auth\UpdateSettingsController@notifications_mail_general');
Route::post('/user/notifications/mail/specific', 'Auth\UpdateSettingsController@notifications_mail_specific');

// CSS Routes
Route::get('/user/settings/css', 'CssController@showUserSettings'); //TODO
Route::post('/user/settings/css', 'CssController@setUserSettings'); //TODO
