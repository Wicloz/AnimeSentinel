<?php

namespace App\AnimeSentinel;

class Downloaders
{
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
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_COOKIESESSION, 1);
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__.'/../../storage/app/cookies/cloudflare.cj');
    curl_setopt($curl, CURLOPT_COOKIEFILE, __DIR__.'/../../storage/app/cookies/cloudflare.cj');
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
      'Cookie: cf_clearance=07a60c26e8eca63a17eb5ab66b144b03a9d90cfe-1469883491-86400',
      'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0',
      'Accept: text/html',
    ]);
    curl_setopt($curl, CURLOPT_COOKIE, 'cf_clearance=07a60c26e8eca63a17eb5ab66b144b03a9d90cfe-1469883491-86400');
    $response = curl_exec($curl);
    curl_close($curl);

    if (strpos($response, '<title>Please wait 5 seconds...</title>') !== false) {
      // get cookies
      //$response = downloadCloudFlare($url, $cookieid);
    }

    return $response;
  }
}
