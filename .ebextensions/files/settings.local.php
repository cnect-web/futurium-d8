<?php

/**
 * Config directories.
 */
$config_directories['sync'] = '../config/sync';

/**
 * Databases.
 */
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


/**
 * Hash salt.
 */
$settings['hash_salt'] = $_SERVER['HASH_SALT'];

/**
 * Config Split.
 */
$config['config_split.config_split.dev_config']['status'] = FALSE;
$config['config_split.config_split.staging_config']['status'] = FALSE;
$config['config_split.config_split.prod_config']['status'] = FALSE;
switch (getenv('ENVIRONMENT')) {
  case 'production':
    $config['config_split.config_split.prod_config']['status'] = TRUE;
    break;

  case 'staging':
    $config['config_split.config_split.staging_config']['status'] = TRUE;
    break;

  default:
    $config['config_split.config_split.dev_config']['status'] = TRUE;
    // Enable local development services.
    $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/development.services.yml';
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    $settings['cache']['bins']['render'] = 'cache.backend.null';
    $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
    break;
}