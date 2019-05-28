<?php

$databases['default']['default'] = array (
  'database' => $_SERVER['DATABASE_NAME'],
  'username' => $_SERVER['DATABASE_USERNAME'],
  'password' => $_SERVER['DATABASE_PASSWORD'],
  'prefix' => '',
  'host' => $_SERVER['DATABASE_HOST'],
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

$settings['hash_salt'] = $_SERVER['HASH_SALT'];
