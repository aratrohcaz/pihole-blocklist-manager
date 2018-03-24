<?php

class SetupManager {

  // Cache root_dir because calling realpath a lot of times -can- cause some slowness
  private $root_dir = null;

  const TEMP_DIR_NAME = 'tmp';
  const OUT_DIR_NAME = 'compiled';
  /**
  * Checks the setup of the project and creates any missing directories
  * Returns true on successfully setup project, false if otherwise
  *
  * @param bool $create_missing_directories
  *
  * @return bool
  */
  public function checkProject($create_missing_directories = true)
  {
    if ($this->getRootDirectory() === null) {
      throw new Exception('Unable to get root directory, something is terribly wrong (getRootDirectory returned null)');
    }

    $errors = array();
    $directories_needed = array(
      $this->getTempDirectory(),
      $this->getOutputDirectory(),
    );

    foreach ($directories_needed as $directory_needed) {
      if (!file_exists($directory_needed)) {
        echo 'Directory \'' . $directory_needed . '\' does not exist ' . PHP_EOL;
        if ($create_missing_directories) {
          echo 'Attempting to create directory \'' . $directory_needed . '\'' . PHP_EOL;
          if (!mkdir($directory_needed)) {
            $errors[$directory_needed] = array('Unable to make directory ' . $directory_needed);
          }
          //TODO add fixing of mode for directory
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
  public function getRootDirectory()
  {
    if ($this->root_dir === null) {
      $this->root_dir = realpath(implode(DIRECTORY_SEPARATOR, array(
        __DIR__,
        '..',
        '..',
      )));
    }

    return $this->root_dir;
  }

  /**
  * @return string
  */
  public function getOutputDirectory()
  {
    return $this->getRootDirectory() . DIRECTORY_SEPARATOR . SetupManager::OUT_DIR_NAME;
  }

  /**
  * @return string
  */
  public function getTempDirectory()
  {
    return $this->getRootDirectory() . DIRECTORY_SEPARATOR . SetupManager::TEMP_DIR_NAME;
  }


}
