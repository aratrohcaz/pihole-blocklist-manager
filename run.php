<?php
  /**
   * Created by PhpStorm.
   * User: Zac
   * Date: 1/04/2018
   * Time: 09:12
   */

  /**
   * Nice Stuff for not quick-and-dirty verion
   * 1 - better error handling
   * 2 - not creating a new curl object for every request
   * 3 - printing memory usage
   * 4 - method to check if files have changed??
   * 5 - custom extensions for list files
   * 6 - de-duplicating urls from ini file
   * 7 - custom (sic. user set-able) backup timestamp format
   */

  $timestamp = date('Ymd_his');
  printLog('info', 'Backup file Timestamp is ' . $timestamp);

  $config_file = __DIR__ . DIRECTORY_SEPARATOR . 'config.ini';
  if (!file_exists($config_file)) {
    file_put_contents($config_file, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'sample-config.ini'));
    throw new Exception('Config file not found, created a blank config from template file');
  }

  $temp_dir = __DIR__ . DIRECTORY_SEPARATOR . 'temp';
  if (!file_exists($temp_dir)) {
    if (!mkdir($temp_dir)) {
      throw new Exception('Unable to make director \'' . $temp_dir . '\'');
    }
  }

  $out_dir = __DIR__ . DIRECTORY_SEPARATOR . 'output';
  if (!file_exists($out_dir)) {
    if (!mkdir($out_dir)) {
      throw new Exception('Unable to make director \'' . $out_dir . '\'');
    }
  }

  $config = parse_ini_file($config_file, true);
//  print_r($config);

  // Download lists
  $parse_files = array();
  if (isset($config['blacklist-urls'])) {
    foreach ($config['blacklist-urls'] as $url_key => $blacklist_url) {

      $list_content = getUrlContent($blacklist_url);
      if ($list_content !== false) {
        $temp_path = $temp_dir . DIRECTORY_SEPARATOR . $url_key . '.list';
        printLog('info', 'Downloaded, putting data into \'' . $temp_path . '\'');
        // move the old file
        if (file_exists($temp_path)) {
          rename($temp_path, $temp_path . '-' . $timestamp . '.bak');
        }

        file_put_contents($temp_path, $list_content);
        $parse_files[] = $temp_path;
      }
    }
  }

  // Combine files


  printLog('info', 'Done!');


  #region Handy Functions
  function getUrlContent($url, $timeout = 5)
  {
    printLog('curl', 'Attempting to fetch \'' . $url . '\'');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
//    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    $data      = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (($http_code >= 200) && ($http_code < 300)) {
      return $data;
    }

    printLog('curl-debug', 'Request returned a code of \'' . $http_code . '\', could not get file.');

    return false;
  }

  function printLog($section = 'info', $message = '')
  {
    printf('%-15s >> %s' . PHP_EOL, $section, $message);
  }

  #endregion Handy Functions

