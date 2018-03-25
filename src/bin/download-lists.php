<?php
  require_once implode(DIRECTORY_SEPARATOR, array(
    __DIR__,
    '..',
    '..',
    'vendor',
    'autoload.php',
  ));

// TODO move this?

  if (!SetupManager::checkProject(true)) {
    exit(1);
  }
