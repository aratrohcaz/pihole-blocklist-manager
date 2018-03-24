<?php
require_once implode(DIRECTORY_SEPARATOR, array(
  __DIR__,
  '..',
  '..',
  'vendor',
  'autoload.php',
));

// TODO move this?
$setup = new SetupManager();
if (!$setup->checkProject( true)) {
  exit(1);
}
