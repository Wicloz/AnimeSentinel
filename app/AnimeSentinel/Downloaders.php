<?php

namespace App\AnimeSentinel;

class Downloaders
{
  /**
   * Downloads a webpage after determining whivh method to use.
   *
   * @return string
   */
  public static function downloadPage($url) {
    if (strpos($url, 'kissanime.to') !== false) {
      $response = Self::downloadCloudFlare($url, 'kissanime');
    } elseif (strpos($url, 'gogoanime.io') !== false) {
      $response = Self::downloadCloudFlare($url, 'gogoanime');
    }

    else {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      $response = htmlentities_decode(curl_exec($curl));
      curl_close($curl);
    }

    $replace = [
      '┬á' => ' ',
      '╬¿' => 'Ψ',
      '╬ö' => 'Δ',
    ];
    foreach ($replace as $from => $to) {
      $response = str_replace($from, $to, $response);
    }

    return $response;
  }

  /**
   * Returns a webpage after executing JavaScript
   *
   * @return string
   */
  public static function downloadJavaScript($url) {
    return ""; //TODO
  }

  /**
   * Downloads webpages behind a cloudflare filter.
   *
   * @return string
   */
  public static function downloadCloudFlare($url, $cookieid = 'cloudflare') {
    if (file_exists(__DIR__.'/../../storage/app/cookies/'.$cookieid)) {
      $cf_data = json_decode(file_get_contents(__DIR__.'/../../storage/app/cookies/'.$cookieid));
    } else {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadCloudFlare($url, $cookieid);
    }

    if ($cookieid === 'kissanime') {
      $cf_data->cookies .= '; password='.config('animesentinel.kissanime_password_cookie').'; username='.config('animesentinel.kissanime_username_cookie');
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

    if (strpos($response, '<title>Please wait 5 seconds...</title>') !== false) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadCloudFlare($url, $cookieid);
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
