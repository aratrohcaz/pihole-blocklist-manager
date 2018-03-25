<?php

  class FileManager
  {

    const BLACKLIST_FILE                = 'blacklist-in.list';
    const BLACKLIST_MANUAL_ENTRIES_FILE = 'blacklist-manual.list';
    const WHITELIST_MANUAL_ENTRIES_FILE = 'whitelist-manual.list';

    const TEMP_DIR_NAME = 'tmp';
    const OUT_DIR_NAME  = 'compiled';

    const CURL_TIMEOUT = 30; // Seconds

    // Cache root_dir because calling realpath a lot of times -can- cause some slowness
    private static $root_dir = null;
    private static $curl_obj = null;

    /**
     * @return null|string
     */
    public static function getRootDirectory()
    {
      if (static::$root_dir === null) {
        static::$root_dir = realpath(implode(DIRECTORY_SEPARATOR, array(
          __DIR__,
          '..',
          '..',
        )));
      }

      return static::$root_dir;
    }

    /**
     * @return string
     */
    public static function getOutputDirectory()
    {
      return static::getRootDirectory() . DIRECTORY_SEPARATOR . static::OUT_DIR_NAME;
    }

    /**
     * @return string
     */
    public static function getTempDirectory()
    {
      return static::getRootDirectory() . DIRECTORY_SEPARATOR . static::TEMP_DIR_NAME;
    }


    public static function getFileFromURL($url = null, $custom_name = null)
    {
      if ($url === null) {
        throw new Exception('null URL given');
      }
      $curl_obj = static::getCurlObj();
      /** @var $curl_obj resource */
      curl_setopt($curl_obj, CURLOPT_URL, $url);

      $response_body = curl_exec($curl_obj);
      $info          = curl_getinfo($curl_obj);

      if ($response_body === false || $info['http_code'] != 200) {
        $response_body = "No cURL data returned for $url [" . $info['http_code'] . "]";
        if (curl_error($curl_obj)) {
          $response_body .= "\n" . curl_error($curl_obj);
        }
      }
    }

    /**
     *
     * @return null|resource
     */
    public static function getCurlObj()
    {
      if (static::$curl_obj === null) {
        static::$curl_obj = static::initCurlObj();
      }

      return static::$curl_obj;
    }

    /**
     * Creates a Curl resource with some sane defaults
     *
     * @return resource
     */
    private static function initCurlObj()
    {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_TIMEOUT, static::CURL_TIMEOUT);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      return $ch;
    }
  }
