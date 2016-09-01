<?php

namespace App\AnimeSentinel;

class Downloaders
{
  /**
   * Downloads a webpage after determining whivh method to use.
   *
   * @return string
   */
  public static function downloadPage($url, $tries = 0) {
    $url = preg_replace_callback('/([^a-zA-Z0-9\\=\\:\\+\\&\\?\\-\\_\\/\\\\])/u', function($matches) {
      return urlencode($matches[1]);
    }, $url);

    if (str_contains($url, 'kissanime.to')) {
      $response = Self::downloadCloudFlare($url, 'kissanime', $tries);
    } elseif (str_contains($url, 'kisscartoon.me')) {
      $response = Self::downloadCloudFlare($url, 'kisscartoon', $tries);
    } elseif (str_contains($url, 'gogoanime.io')) {
      $response = Self::downloadCloudFlare($url, 'gogoanime', $tries);
    }

    else {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      $response = htmlentities_decode(curl_exec($curl));
      curl_close($curl);
    }

    return $response;
  }

  /**
   * Returns a webpage after executing JavaScript
   *
   * @return string
   */
  private static function downloadJavaScript($url) {
    return ""; //TODO
  }

  /**
   * Downloads webpages behind a cloudflare filter.
   *
   * @return string
   */
  private static function downloadCloudFlare($url, $cookieid = 'cloudflare', $tries = 0) {
    if (file_exists(__DIR__.'/../../storage/app/cookies/'.$cookieid)) {
      $cf_data = json_decode(file_get_contents(__DIR__.'/../../storage/app/cookies/'.$cookieid));
    }
    if (empty($cf_data)) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
    }

    if ($cookieid === 'kissanime') {
      $cf_data->cookies .= '; password='.config('animesentinel.kissanime_password_cookie').'; username='.config('animesentinel.kissanime_username_cookie');
    } elseif ($cookieid === 'kisscartoon') {
      $cf_data->cookies .= '; password='.config('animesentinel.kisscartoon_password_cookie').'; username='.config('animesentinel.kisscartoon_username_cookie');
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'Cookie: '.$cf_data->cookies,
      'User-Agent: '.$cf_data->agent,
    ]);
    $response = htmlentities_decode(curl_exec($curl));
    curl_close($curl);

    if (str_contains($response, '<title>Please wait 5 seconds...</title>')) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
    }

    if ($tries === 0 && str_contains($response, 'https://www.google.com/recaptcha/api.js')) {
      \Mail::send('emails.report_general', ['description' => 'ReCaptcha Detected', 'vars' => [
        'Url' => $url,
      ]], function ($m) {
        $m->subject('AnimeSentinel Detected ReCaptcha');
        $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
        $m->to('animesentinel@wilcodeboer.me');
      });
    }
    if (str_contains(preg_replace('/\s+/', '', $response), '<title>AreYouHuman</title>')) {
      exec('python '. __DIR__ .'/ReCaptcha.py "'. $url .'" "'. $cf_data->agent .'"');
      return Self::downloadPage($url, $tries + 1);
    }

    return $response;
  }

  private static function requestCloudFlareData($url, $cookieid = 'cloudflare') {
    $cookies = exec('python '. __DIR__ .'/CloudFlare.py "'. $url .'"');
    if (!file_exists(__DIR__.'/../../storage/app/cookies')) {
      mkdir(__DIR__.'/../../storage/app/cookies');
    }
    file_put_contents(__DIR__.'/../../storage/app/cookies/'.$cookieid, $cookies);
  }
}
