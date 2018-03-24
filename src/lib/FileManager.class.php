<?php

class FileManager {

  const BLACKLIST_FILE = 'blacklist-in.list';
  const BLACKLIST_MANUAL_ENTRIES_FILE = 'blacklist-manual.list';
  const WHITELIST_MANUAL_ENTRIES_FILE = 'whitelist-manual.list';

  const TEMP_DIR_NAME = 'tmp';
  const OUT_DIR_NAME = 'compiled';

  // Cache root_dir because calling realpath a lot of times -can- cause some slowness
  private static $root_dir = null;

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

}
