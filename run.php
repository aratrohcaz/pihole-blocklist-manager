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
   * 12 - Info about how many lines had been saved
   */

  #region Check for timezone being set
  if (strlen(ini_get('date.timezone')) === 0) {
    ini_set('date.timezone', 'UTC');
    printLog('warning', 'Timezone not set in php.ini, using timezone UTC');
  }
  #endregion Check for timezone being set

  #region Rudimentary settings area
  $timestamp        = date('Ymd_his');
  $output_file_name = 'compiled.txt';
  $do_downloading   = true;
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

  #region Downloading lists
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
  $downloaded_count = count($downloaded_files);
  printLog('info', sprintf('Downloaded %d file%s',
      $downloaded_count,
      ($downloaded_count > 1 ? 's' : '')
    )
  );
  #endregion Downloading lists

  #region Parsing Files
  printLog('info', 'Combining lists and de-duplicating..');

  $existing_files  = glob($temp_dir . DIRECTORY_SEPARATOR . '*.list');
  $parse_files     = array_unique(array_merge($downloaded_files, $existing_files));
  $number_of_files = count($parse_files);
  printLog('stats', 'Reduced lists to ' . $number_of_files . ' entr' . ($number_of_files > 1 ? 'ies' : 'y'));
  // variable cleanup
  unset($existing_files, $downloaded_files);

  printLog('notice', 'Combining all entries into a single file for sorting');
  $parse_ctr            = 0;
  $deduplicated_records = array();

  $comment_chars = array('#', ';');
  printLog('info', 'Will be skipping lines starting with ' . implode(',', $comment_chars));

  $total_lines = 0;
  foreach ($parse_files as $parse_file) {
    $parse_ctr++;
    // This is where it would regex out the 0.0.0.0 from host files (potentially also remove the http[s]:// from the front if present
    $lines = file($parse_file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
    printLog('debug', sprintf('Loaded \'%s\', has %d lines (inc. comments)', $parse_file, count($lines)));
    $non_comment_lines = 0;
    foreach ($lines as $line) {
      $first_char = substr(trim($line), 0, 1);
      if (!in_array($first_char, $comment_chars)) {
        $non_comment_lines++;
        // check if it is in hosts file format and remove the ip prefix from the front - we're going to cheat, because
        // we know it will contain a space, so we'll explode on that and use the last part which should be the domain
        $parts = explode(' ', $line);
        if (count($parts) > 1) {
          $line = array_pop($parts);
        }
        $hash = md5($line);
        if (!isset($deduplicated_records[$hash])) {
          $deduplicated_records[$hash] = array('hits' => 0, 'url' => $line);
        }
        $deduplicated_records[$hash]['hits']++;
      }
    }
    $total_lines += $non_comment_lines;
    printLog('progress', sprintf('Processed %s of %s (%d lines added)', $parse_ctr, $number_of_files, $non_comment_lines));
    // Remove the lines of the file from memory, any others no longer used
    unset($lines, $line, $hash, $first_char, $non_comment_lines);
  }

  printLog('stats', sprintf('Narrowed lists down from %d to %s lines, %01.2f%% reduction',
    $total_lines,
    count($deduplicated_records),
    100 - ((count($deduplicated_records) / $total_lines) * 100)) // this seems wrong?
  );
  #endregion Parsing Files

  #region Combine files / Stats
  $output_lines = array();
  $max_hit_url  = null;
  $max_hit_ctr  = 0;

  foreach ($deduplicated_records as $deduplicated_record) {
    if ($deduplicated_record['hits'] > $max_hit_ctr) {
      $max_hit_url = $deduplicated_record['url'];
      $max_hit_ctr = $deduplicated_record['hits'];
    }
    $output_lines[] = $deduplicated_record['url'];
  }
  unset($deduplicated_records);
  printLog('stats', sprintf('Highest was %d hits for %s', $max_hit_ctr, $max_hit_url));
  sort($output_lines); // sort the output array
  #endregion Combine files / Stats

  #region Adding Blacklist Domains
  if (isset($config['blacklist-domains']) && count($config['blacklist-domains'])) {
    printLog('info', 'Adding user blacklisted domains');
    $current_lines = array_flip($output_lines); // isset / key look ups are faster than searches
    foreach ($config['blacklist-domains'] as $blacklist_key => $blacklist_domain) {
      $blacklist_domain = trim($blacklist_domain);
      $message_part     = 'already';
      $message          = 'Domain ' . $blacklist_domain . ' %s blacklisted';
      if (!isset($current_lines[$blacklist_domain])) {
        $output_lines[] = $blacklist_domain; // we add it onto the original list
        $message_part   = 'is now';
      }
      printLog('blacklist', sprintf($message, $message_part));
    }
    unset($current_lines); // We don't need this any more
    sort($output_lines);
  }
  #endregion Adding Blacklist Domains

  #region Removing whitelisted domains
  if (isset($config['whitelist-domains']) && count($config['whitelist-domains'])) {
    printLog('info', 'Adding user blacklisted domains');
    $current_lines = array_flip($output_lines); // isset / key look ups are faster than searches
    foreach ($config['whitelist-domains'] as $whitelist_key => $whitelist_domain) {
      $whitelist_domain = trim($whitelist_domain);
      $message_part     = 'not present in list';
      $message          = 'Domain ' . $whitelist_domain . ' %s';
      if (isset($current_lines[$whitelist_domain])) {
        $position = $current_lines[$whitelist_domain];
        unset($output_lines[$position]);
        $message_part = 'has been removed (from position ' . $position . ')';
      }
      printLog('whitelist', sprintf($message, $message_part));
    }
    unset($current_lines); // We don't need this any more
    // no need to sort here, as we'd still be in order, just missing a few entries
  }

  #region Removing whitelisted domains

  #region Output
  $full_path = $out_dir . DIRECTORY_SEPARATOR . $output_file_name;
  printLog('info', 'Creating single file (' . $full_path . ')');
  $header = array(
    '################################################################### ',
    '# File made by pihole-blocklist-manager at ' . date('Y-m-d H:i.s'),
    '# Built from ' . $number_of_files . ' files, resulting in ~' . count($output_lines) . ' lines',
    '################################################################### ',
    '',
    '',
  );

  file_put_contents($full_path, implode(PHP_EOL, $header));
  file_put_contents($full_path, implode(PHP_EOL, $output_lines), FILE_APPEND);

  printLog('info', 'Done!');
  #endregion Output

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
  function printLog($section = 'info', $message = '', $show_timestamp = false)
  {
    $memory_usage = memory_get_usage(true);
    $memory_unit  = 'b';
    switch ($memory_usage) {
      case  ($memory_usage < 1024):
        break;
      case ($memory_usage < 1048576):
        $memory_usage = round($memory_usage / 1024, 2);
        $memory_unit  = 'K';
        break;
      case ($memory_usage >= 1048576):
        $memory_usage = round($memory_usage / 1048576, 2);
        $memory_unit  = 'M';
        break;
    }

    $timestamp = '';
    if ($show_timestamp) {
      $timestamp = date('Y-m-d H:i.s') . ' ';
    }

    printf(' > %s%-12s [ %6.2f%s ] >> %s ' . PHP_EOL, $timestamp, $section, $memory_usage, $memory_unit, $message);
  }

  #endregion Handy Functions

