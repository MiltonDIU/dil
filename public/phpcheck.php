<?php

echo 'PHP: ' . PHP_VERSION . '<br>';
echo 'PDO: ' . (extension_loaded('pdo') ? 'yes' : 'no') . '<br>';
echo 'pdo_mysql: ' . (extension_loaded('pdo_mysql') ? 'yes' : 'no') . '<br>';
echo 'DOM: ' . (extension_loaded('dom') ? 'yes' : 'no') . '<br>';
