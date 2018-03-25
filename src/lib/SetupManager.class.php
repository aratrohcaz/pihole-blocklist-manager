<?php

  class SetupManager
  {

    /**
     * Checks the setup of the project and creates any missing directories
     * Returns true on successfully setup project, false if otherwise
     *
     * @param bool $create_missing Create missing directories and files
     *
     * @return bool
     * @throws \Exception
     */
    public static function checkProject($create_missing = true)
    {
      if (FileManager::getRootDirectory() === null) {
        throw new Exception('Unable to get root directory, something is terribly wrong (getRootDirectory returned null)');
      }

      $errors             = array();
      $directories_needed = array(
        FileManager::getTempDirectory(),
        FileManager::getOutputDirectory(),
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
        $filename = FileManager::getRootDirectory() . DIRECTORY_SEPARATOR . $required_file;
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

  }
