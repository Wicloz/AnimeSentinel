<?php

namespace App\AnimeSentinel;

class Downloaders
{
  /**
   * Downloads a webpage after determining which method to use.
   *
   * @return string
   */
  public static function downloadPage($url, $tries = 0) {
    $url = preg_replace_callback('/([^a-zA-Z0-9\\%\\=\\:\\+\\&\\?\\-\\_\\/\\\\])/u', function($matches) {
      return urlencode($matches[1]);
    }, $url);

    if ($tries > 7) {
      throw new \Exception('Download Failed: '.$url.' after '.$tries.' tries');
    }

    elseif (str_contains($url, 'kissanime.ru') && str_contains($url, '?id=')) {
      $response = Self::downloadCloudFlare($url, 'kissanime', $tries, true);
    }

    elseif (str_contains($url, 'kissanime.ru')) {
      $response = Self::downloadCloudFlare($url, 'kissanime', $tries);
    }

    else {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
      $response = htmlentities_decode(curl_exec($curl));
      curl_close($curl);
    }

    if (str_contains($response, 'https://www.google.com/recaptcha/api.js')) {
      if ($tries === 0 && !str_contains($url, 'myanimelist.net')) {
        try {
          \Mail::send('emails.reports.general', ['description' => 'ReCaptcha Detected', 'vars' => [
            'Url' => $url,
          ]], function ($m) {
            $m->subject('AnimeSentinel Detected ReCaptcha');
            $m->from('reports.animesentinel@wilcodeboer.me', 'AnimeSentinel Reports');
            $m->to(array_merge(config('mail.debug_addresses'), config('mail.admin_addresses')));
          });
        } catch (\Exception $e) {}
      }
      elseif (str_contains($url, 'kissanime.ru')) {
        throw new \Exception('Download Failed: '.$url.' is locked by a ReCaptcha');
      }
    }

    if ($response === 'Too Many Requests') {
      throw new \Exception('Download Failed: '.$url.' after '.$tries.' tries');
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
  private static function downloadJavaScript($url, $tries, $cookies = '', $useragent = '') {
    // exec('Xvfb :99 -ac 2> /dev/null');
    // return shell_exec('python "'. app_path('AnimeSentinel/Python/GetExpanded.py') .'" "'. $url .'" "'. resource_path('binaries') .'" "'. $cookies .'" "'. $useragent .'"');
    return "";
  }

  /**
   * Downloads webpages behind a cloudflare filter.
   *
   * @return string
   */
  private static function downloadCloudFlare($url, $cookieid = 'cloudflare', $tries, $javascript = false) {
    if (file_exists(storage_path('app/cookies/'.$cookieid))) {
      $cf_data = json_decode(file_get_contents(storage_path('app/cookies/'.$cookieid)));
    }
    if (empty($cf_data)) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
    }

    if ($cookieid === 'kissanime') {
      $cf_data->cookies .= '; passwordK='.config('animesentinel.kissanime_password_cookie').'; usernameK='.config('animesentinel.kissanime_username_cookie');
    }

    if (!$javascript) {
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
    } else {
      $response = Self::downloadJavaScript($url, $tries, $cf_data->cookies, $cf_data->agent);
    }

    if (str_contains($response, '<title>Please wait 5 seconds...</title>')) {
      Self::requestCloudFlareData($url, $cookieid);
      return Self::downloadPage($url, $tries + 1);
    }

    return $response;
  }

  private static function requestCloudFlareData($url, $cookieid = 'cloudflare') {
    $cookies = exec('python "'. app_path('AnimeSentinel/Python/CloudFlare.py') .'" "'. $url .'"');
    if (!file_exists(storage_path('app/cookies/'))) {
      mkdir(storage_path('app/cookies/'));
    }
    file_put_contents(storage_path('app/cookies/'.$cookieid), $cookies);
  }
}
