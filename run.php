<?php
  /**
   * Created by PhpStorm.
   * User: Zac
   * Date: 1/04/2018
   * Time: 09:12
   */

  /**
   * Nice Stuff that could be added
   * 1 - better error handling
   * 2 - not creating a new curl object for every request
   * 3 - printing memory usage
   * 4 - method to check if files have changed??
   * 5 - custom extensions for list files
   * 6 - de-duplicating urls from ini file
   * 7 - custom (sic. user set-able) backup timestamp format
   * 8 - a way to purge the temp directory and refresh all lists
   * 9 - An option to skip downloading files
   * 10 - testing of the urls (from the list) to see if they resolve (issue if the URLs become active again, may/may
   * not be ads at a later point.)
   * 11 - add format choice (host file format (ip domain) or domain list)
   */

  #region Rudimentary settings area
  $timestamp        = date('Ymd_his');
  $output_file_name = 'compiled.txt';
  $do_downloading   = false;
  #endregion Rudimentary settings area

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

  // Download lists
  $downloaded_files = array();
  if ($do_downloading && isset($config['blacklist-urls'])) {
    $num_urls     = count($config['blacklist-urls']);
    $download_ctr = 0;
    printLog('info', 'Found ' . $num_urls . ' URL' . ($num_urls > 1 ? 's' : ''));
    foreach ($config['blacklist-urls'] as $url_key => $blacklist_url) {
      $download_ctr++;
      printLog('debug', 'Working on ' . $download_ctr . ' of ' . $num_urls);
      $list_content = getUrlContent($blacklist_url);
      if ($list_content !== false) {
        $temp_path = $temp_dir . DIRECTORY_SEPARATOR . $url_key . '.list';
        printLog('info', 'Downloaded, putting data into \'' . $temp_path . '\'');
        // move the old file
        if (file_exists($temp_path)) {
          rename($temp_path, $temp_path . '-' . $timestamp . '.bak');
        }

        file_put_contents($temp_path, $list_content);
        $downloaded_files[] = $temp_path;
      }
    }
  }

  $existing_files   = glob($temp_dir . DIRECTORY_SEPARATOR . '*.list');
  $downloaded_count = count($downloaded_files);
  $existing_count   = count($existing_files);

  printLog('info', 'Downloaded ' . $downloaded_count . ' file' . ($downloaded_count > 1 ? 's' : '') . ', ' . $existing_count . ' in temp directory, totalling ' . ($downloaded_count + $existing_count) . 'lists');
  printLog('info', 'Combining lists and de-duplicating..');

  $parse_files     = array_unique(array_merge($downloaded_files, $existing_files));
  $number_of_files = count($parse_files);
  printLog('info', 'Reduced lists to ' . $number_of_files . ' entr' . ($number_of_files > 1 ? 'ies' : 'y'));

  printLog('notice', 'Combining all entries into a single file for sorting');
  $parse_ctr            = 0;
  $deduplicated_records = array();

  $comment_chars = array('#', ';');
  printLog('info', 'Will be skipping lines starting with ' . implode(',', $comment_chars));

  foreach ($parse_files as $parse_file) {
    $parse_ctr++;
    // This is where it would regex out the 0.0.0.0 from host files (potentially also remove the http[s]:// from the front if present
    $lines = file($parse_file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    printLog('debug', sprintf('Loaded \'%s\', has %d lines (inc. commentds)', $parse_file, count($lines)));
    $non_comment_lines = 0;
    foreach ($lines as $line) {
      $first_char = substr(trim($line), 0, 1);
      if (!in_array($first_char, $comment_chars)) {
        $non_comment_lines++;
        // check if it is in hosts file format and remove the ip prefix from the front - we're going to cheat, because
        // we know it will contain a space, so we'll explode on that and use the last part which should be the domain
        $parts = explode(' ', $line);
        if (count($parts) > 1) {
          $line = array_shift($parts);
        }
        $hash = md5($line);
        if (!isset($deduplicated_records[$hash])) {
          $deduplicated_records[$hash] = array('hits' => 0, 'url' => $line);
        }
        $deduplicated_records[$hash]['hits']++;
      }
    }
    printLog('progress', sprintf('Processed %s of %s (%d lines added)', $parse_ctr, $number_of_files, $non_comment_lines));
  }

  // Combine files / Gather stats
//  print_r($output_records);
  printLog('info', 'Narrowed lists down to ' . count($deduplicated_records) . ' records');
  $full_path = $out_dir . DIRECTORY_SEPARATOR . $output_file_name;
  printLog('info', 'Creating single file (' . $full_path . ')');

  $header       = array(
    '################################################################### ',
    '# File made by pihole-blocklist-manager as ' . date('Y-m-d H:i.s'),
    '# Built from ' . $number_of_files . ' files, ~' . count($deduplicated_records) . ' lines parsed',
    '################################################################### ',
    '',
    '',
  );
  $output_lines = array();
  // could be array walk?
  foreach ($deduplicated_records as $deduplicated_record) {
    $output_lines[] = $deduplicated_record['url'];
  }

  sort($output_lines); // sort the output array

  file_put_contents($full_path, implode(PHP_EOL, $header));
  file_put_contents($full_path, implode(PHP_EOL, $output_lines), FILE_APPEND);


  printLog('info', 'Done!');

  #region Handy Functions
  /**
   * @param     $url
   * @param int $conn_timeout        Timeout to establish the connection
   * @param int $transaction_timeout Timeout for the transfer time (too long to get all data)
   *
   * @return bool|mixed
   */
  function getUrlContent($url, $conn_timeout = 5, $transaction_timeout = 600)
  {
    printLog('curl', 'Attempting to fetch \'' . $url . '\'');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conn_timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $transaction_timeout);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $data      = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (($http_code >= 200) && ($http_code < 300)) {
      return $data;
    }

    printLog('curl-debug', 'Request returned a code of \'' . $http_code . '\', could not get file.');

    return false;
  }

  /**
   * @param string $section
   * @param string $message
   *
   * @void
   */
  function printLog($section = 'info', $message = '')
  {
    printf(' %-15s >> %s' . PHP_EOL, $section, $message);
  }

  #endregion Handy Functions

