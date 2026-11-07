<?php

header('Content-Type: text/plain');

echo 'PHP: ' . PHP_VERSION . PHP_EOL;
echo 'SAPI: ' . PHP_SAPI . PHP_EOL;
echo 'INI: ' . (php_ini_loaded_file() ?: 'none') . PHP_EOL;
echo 'Scanned INIs: ' . (php_ini_scanned_files() ?: 'none') . PHP_EOL;
echo 'fileinfo: ' . (extension_loaded('fileinfo') ? 'YES' : 'NO') . PHP_EOL;
echo 'finfo: ' . (class_exists('finfo') ? 'YES' : 'NO') . PHP_EOL;