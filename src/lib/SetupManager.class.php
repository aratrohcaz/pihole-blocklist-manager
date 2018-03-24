<?php

class SetupManager {

  // Cache root_dir because calling realpath a lot of times -can- cause some slowness
  private static $root_dir = null;

  const TEMP_DIR_NAME = 'tmp';
  const OUT_DIR_NAME = 'compiled';

  /**
  * Checks the setup of the project and creates any missing directories
  * Returns true on successfully setup project, false if otherwise
  *
  * @param bool $create_missing Create missing directories and files
  *
  * @return bool
  */
  public static function checkProject($create_missing = true)
  {
    if (static::getRootDirectory() === null) {
      throw new Exception('Unable to get root directory, something is terribly wrong (getRootDirectory returned null)');
    }

    $errors = array();
    $directories_needed = array(
      static::getTempDirectory(),
      static::getOutputDirectory(),
    );

    foreach ($directories_needed as $directory_needed) {
      if (!file_exists($directory_needed)) {
        echo 'Directory \'' . $directory_needed . '\' does not exist ' . PHP_EOL;
        if ($create_missing) {
          echo 'Attempting to create directory \'' . $directory_needed . '\'' . PHP_EOL;
          if (!mkdir($directory_needed)) {
            $errors[$directory_needed . '_create'] = 'Unable to make directory ' . $directory_needed;
          }
          //TODO add fixing of mode for directory

        } else {
          $errors[$directory_needed . '_create'] = 'Directory ' . $directory_needed . ' does not exists, $create_missing is false';
        }
      }
    }

    // Check for the required files
    $required_files = array(
      FileManager::BLACKLIST_FILE,
      FileManager::BLACKLIST_MANUAL_ENTRIES_FILE,
      FileManager::WHITELIST_MANUAL_ENTRIES_FILE,
    );

    foreach ($required_files as $required_file) {
      $filename = static::getRootDirectory() . DIRECTORY_SEPARATOR . $required_file;
      if (!is_readable($filename)) {
        echo $filename . ' is not readable, checking if it exists';
        $status = 'File exists, check read permissions on file';
        if (!file_exists($filename)) {
          $status = 'File does not exist. Creating empty file';
          touch($filename);
        }

      }
    }


    if (!empty($errors)) {
      //TODO outputting of errors
      return false;
    }

    return true;
  }

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
    return static::getRootDirectory() . DIRECTORY_SEPARATOR . SetupManager::OUT_DIR_NAME;
  }

  /**
  * @return string
  */
  public static function getTempDirectory()
  {
    return static::getRootDirectory() . DIRECTORY_SEPARATOR . SetupManager::TEMP_DIR_NAME;
  }


}
