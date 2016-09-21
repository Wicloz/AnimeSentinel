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
    $url = preg_replace_callback('/([^a-zA-Z0-9\\%\\=\\:\\+\\&\\?\\-\\_\\/\\\\])/u', function($matches) {
      return urlencode($matches[1]);
    }, $url);

    if ($tries > 9) {
      throw new \Exception('Downloading of the page at '.$url.' failed after 10 tries.');
    }

    if (str_contains($url, 'kissanime.to')) {
      $response = Self::downloadCloudFlare($url, 'kissanime', $tries);
    } elseif (str_contains($url, 'gogoanime.io')) {
      $response = Self::downloadCloudFlare($url, 'gogoanime', $tries);
    }

    elseif (str_contains($url, 'htvanime.com')) {
      $response = Self::downloadJavaScript($url, $tries);
    }

    elseif (str_contains($url, 'myanimelist.net/animelist/')) {
      $response = Self::downloadScrolled($url, $tries);
    }

    else {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      $response = htmlentities_decode(curl_exec($curl));
      curl_close($curl);
    }

    if (empty($response) || $response === 'The service is unavailable.' || str_contains($response, '500 - Internal server error.')) {
      $response = Self::downloadPage($url, $tries + 1);
    }

    return $response;
  }

  /**
   * Returns a webpage after executing JavaScript.
   *
   * @return string
   */
  private static function downloadJavaScript($url, $tries) {
    return shell_exec('python "'. app_path('AnimeSentinel/Python/GetExpanded.py') .'" "'. $url .'" 2> /dev/null');
  }

  /**
   * Returns a webpage after scrolling to the bottom.
   *
   * @return string
   */
  private static function downloadScrolled($url, $tries) {
    return shell_exec('python "'. app_path('AnimeSentinel/Python/GetScrolled.py') .'" "'. $url .'" 2> /dev/null');
  }

  /**
   * Downloads webpages behind a cloudflare filter.
   *
   * @return string
   */
  private static function downloadCloudFlare($url, $cookieid = 'cloudflare', $tries = 0) {
    if (file_exists(storage_path('app/cookies/'.$cookieid))) {
      $cf_data = json_decode(file_get_contents(storage_path('app/cookies/'.$cookieid)));
    }
    if (empty($cf_data)) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
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

    if (str_contains($response, '<title>Please wait 5 seconds...</title>')) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
    }

    if ($tries === 0 && str_contains($response, 'https://www.google.com/recaptcha/api.js')) {
      \Mail::send('emails.reports.general', ['description' => 'ReCaptcha Detected', 'vars' => [
        'Url' => $url,
      ]], function ($m) {
        $m->subject('AnimeSentinel Detected ReCaptcha');
        $m->from('reports@animesentinel.tv', 'AnimeSentinel Reports');
        $m->to('animesentinel@wilcodeboer.me');
      });
    }
    if (str_contains(preg_replace('/\s+/', '', $response), '<title>AreYouHuman</title>')) {
      exec('xvfb-run python "'. app_path('AnimeSentinel/Python/ReCaptcha.py') .'" "'. $url .'" "1" "3" "btnSubmit" "'. $cf_data->agent .'" 2> /dev/null');
      return Self::downloadPage($url, $tries + 1);
    }

    return $response;
  }

  private static function requestCloudFlareData($url, $cookieid = 'cloudflare') {
    $cookies = exec('python "'. app_path('AnimeSentinel/Python/CloudFlare.py') .'" "'. $url .'" 2> /dev/null');
    if (!file_exists(storage_path('app/cookies/'))) {
      mkdir(storage_path('app/cookies/'));
    }
    file_put_contents(storage_path('app/cookies/'.$cookieid), $cookies);
  }
}
