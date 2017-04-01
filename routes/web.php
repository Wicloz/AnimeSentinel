<?php

// \App\AnimeSentinel\Actions\ShowManager::addShowWithTitle('a234sdasd', true);
// queueJob((new \App\Jobs\AnimeFindVideos(\App\Show::withTitle('Drifters')->first()))->delay(\Carbon\Carbon::now()->addDays(2)), 'default');
// queueJob(new \App\Jobs\AnimeReprocessEpisodes(\App\Show::withTitle('Drifters')->first(), ['sub', 'dub'], null, null), 'high');
// \App\AnimeSentinel\Actions\FindVideos::findVideosForShow(\App\Show::withTitle('Tales of Zestiria the X')->first());
// \App\AnimeSentinel\Actions\FindVideos::reprocessEpsiodes(\App\Show::withTitle('Drifters')->first(), ['sub', 'dub'], null, null);
// dd(\App\AnimeSentinel\Connectors\kissanime::findRecentlyAired());
// dd(str_slugify('Yu-Gi-Oh! Arc-V'));
// $test1; $test2;
// $test3 = exec('python3 --version', $test1, $test2);
// dd($test1, $test2, $test3);
// system('python --version &> log');
// dd(exec('python "/home/vagrant/Projects/AnimeSentinel/app/AnimeSentinel/Python/CloudFlare.py" "http://kissanime.ru/" "/home/vagrant/Projects/AnimeSentinel/storage/app/cookies/kissanime"'));
// dd(\App\AnimeSentinel\Downloaders::downloadPage("http://kissanime.ru/Anime/Tales-of-Zestiria-the-X-Cross/Episode-001?id=127417"));

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

// Post Controller
Route::post('/anime/add', 'PostController@showAdd');
Route::post('/anime/recache', 'PostController@showUpdate');
Route::post('/anime/revideos', 'PostController@showRefreshVideos');
Route::post('/anime/reprocess', 'PostController@animeReprocessEpisodes');

// Welcome Page
Route::get('/', 'AnimeController@home');

// Information Pages
Route::get('/about', 'PagesController@about');
Route::get('/about/mal', 'PagesController@about_mal'); //TODO
Route::get('/about/broken', 'PagesController@about_broken'); //TODO
Route::get('/about/donators', 'PagesController@about_donators'); //TODO
Route::get('/about/dmca', 'PagesController@about_dmca'); //TODO
Route::get('/news', 'PagesController@news'); //TODO

// Streamers Pages
Route::get('/streamers', 'StreamersController@list'); //TODO
Route::get('/streamers/{streamer}', 'StreamersController@details'); //TODO

// Profile Pages
Route::get('/anime/overview', 'UserController@overview');
Route::get('/user/settings', 'UserController@settings_general');
Route::get('/user/notifications/mail', 'UserController@settings_nots_mail');

// Profile Modifications
Route::post('/user/settings/general', 'Auth\UpdateSettingsController@general');
Route::post('/user/settings/mal', 'Auth\UpdateSettingsController@mal');
Route::post('/user/settings/password', 'Auth\UpdateSettingsController@password');
Route::post('/user/notifications/mail/general', 'Auth\UpdateSettingsController@notifications_mail_general');
Route::post('/user/notifications/mail/specific', 'Auth\UpdateSettingsController@notifications_mail_specific');
Route::post('/user/settings/overview', 'Auth\UpdateSettingsController@overview');

// Updating MAL data
Route::post('/user/setmal/add', 'Auth\UpdateMalController@add');
Route::post('/user/setmal/full', 'Auth\UpdateMalController@full');
Route::post('/user/setmal/status', 'Auth\UpdateMalController@status');
Route::post('/user/setmal/progres', 'Auth\UpdateMalController@epsWatched');
Route::post('/user/setmal/score', 'Auth\UpdateMalController@score');

// CSS Routes
Route::get('/user/settings/css', 'CssController@showUserSettings'); //TODO
Route::post('/user/settings/css', 'CssController@setUserSettings'); //TODO

// Anime Listings
Route::get('/anime/browse', 'AnimeController@search');
Route::get('/anime/recent', 'AnimeController@recent');
Route::post('/anime/setdisplay', 'AnimeController@setDisplay');
Route::post('/anime/recent/setdistinct', 'AnimeController@setDistinct');
Route::post('/anime/recent/setttype', 'AnimeController@setTtype');

// Anime Details
Route::get('/anime/{show}/{title?}', 'ShowController@details');
Route::get('/anime/{show}/{title?}/series', 'ShowController@series');

// Stream Pages
Route::get('/anime/{show}/{title}/{translation_type}/episode-{episode_num}', 'EpisodeController@gotoEpisode');
Route::get('/anime/{show}/{title}/{translation_type}/episode-{episode_num}/{streamer}/{mirror}', 'EpisodeController@episode');
Route::get('/stream/{video}/video', 'EpisodeController@static');
