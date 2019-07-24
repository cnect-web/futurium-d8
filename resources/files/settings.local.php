<?php

// @codingStandardsIgnoreFile

/**
 * @file
 * Local override configurations.
 */
if (!isset($_SERVER['DATABASE_NAME'])) {
  if (file_exists($app_root . '/../.env')) {
    // Load .env file from project root.
    $dotenv = new Dotenv\Dotenv($app_root . "/../");
    $dotenv->load();
  }
}

$settings['hash_salt'] = '';

/**
 * Databases.
 */
$databases['default']['default'] = array (
  'database' => ($_SERVER['DATABASE_NAME']) ?? getenv('DATABASE_NAME'),
  'username' => ($_SERVER['DATABASE_USERNAME']) ?? getenv('DATABASE_USERNAME'),
  'password' => ($_SERVER['DATABASE_PASSWORD']) ?? getenv('DATABASE_PASSWORD'),
  'prefix' => '',
  'host' => ($_SERVER['DATABASE_HOST']) ?? getenv('DATABASE_HOST'),
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);

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
    /**
     * Assertions.
     *
     * The Drupal project primarily uses runtime assertions to enforce the
     * expectations of the API by failing when incorrect calls are made by code
     * under development.
     *
     * @see http://php.net/assert
     * @see https://www.drupal.org/node/2492225
     *
     * If you are using PHP 7.0 it is strongly recommended that you set
     * zend.assertions=1 in the PHP.ini file (It cannot be changed from .htaccess
     * or runtime) on development machines and to 0 in production.
     *
     * @see https://wiki.php.net/rfc/expectations
     */
    assert_options(ASSERT_ACTIVE, TRUE);
    \Drupal\Component\Assertion\Handle::register();

    /**
     * Enable development config split.
     */
    $config['config_split.config_split.dev_config']['status'] = TRUE;

    /**
     * Enable local development services.
     */
    $settings['container_yamls'][] = DRUPAL_ROOT . '/sites/default/development.services.yml';

    /**
     * Show all error messages, with backtrace information.
     *
     * In case the error level could not be fetched from the database, as for
     * example the database connection failed, we rely only on this value.
     */
    $config['system.logging']['error_level'] = 'verbose';

    /**
     * Disable CSS and JS aggregation.
     */
    $config['system.performance']['css']['preprocess'] = FALSE;
    $config['system.performance']['js']['preprocess'] = FALSE;

    /**
     * Enable access to rebuild.php.
     *
     * This setting can be enabled to allow Drupal's php and database cached
     * storage to be cleared via the rebuild.php page. Access to this page can also
     * be gained by generating a query string from rebuild_token_calculator.sh and
     * using these parameters in a request to rebuild.php.
     */
    $settings['rebuild_access'] = TRUE;

    /**
     * Disable the render cache.
     *
     * Note: you should test with the render cache enabled, to ensure the correct
     * cacheability metadata is present. However, in the early stages of
     * development, you may want to disable it.
     *
     * This setting disables the render cache by using the Null cache back-end
     * defined by the development.services.yml file above.
     *
     * Only use this setting once the site has been installed.
     */
    $settings['cache']['bins']['render'] = 'cache.backend.null';

    /**
     * Disable Internal Page Cache.
     *
     * Note: you should test with Internal Page Cache enabled, to ensure the correct
     * cacheability metadata is present. However, in the early stages of
     * development, you may want to disable it.
     *
     * This setting disables the page cache by using the Null cache back-end
     * defined by the development.services.yml file above.
     *
     * Only use this setting once the site has been installed.
     */
    $settings['cache']['bins']['page'] = 'cache.backend.null';

    /**
     * Disable Dynamic Page Cache.
     *
     * Note: you should test with Dynamic Page Cache enabled, to ensure the correct
     * cacheability metadata is present (and hence the expected behavior). However,
     * in the early stages of development, you may want to disable it.
     */
    $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

    /**
     * Disable caching for migrations.
     *
     * Uncomment the code below to only store migrations in memory and not in the
     * database. This makes it easier to develop custom migrations.
     */
    $settings['cache']['bins']['discovery_migration'] = 'cache.backend.memory';

    /**
     * Allow test modules and themes to be installed.
     *
     * Drupal ignores test modules and themes by default for performance reasons.
     * During development it can be useful to install test extensions for debugging
     * purposes.
     */
    $settings['extension_discovery_scan_tests'] = FALSE;

    /**
     * Enable access to rebuild.php.
     *
     * This setting can be enabled to allow Drupal's php and database cached
     * storage to be cleared via the rebuild.php page. Access to this page can also
     * be gained by generating a query string from rebuild_token_calculator.sh and
     * using these parameters in a request to rebuild.php.
     */
    $settings['rebuild_access'] = TRUE;

    /**
     * Skip file system permissions hardening.
     *
     * The system module will periodically check the permissions of your site's
     * site directory to ensure that it is not writable by the website user. For
     * sites that are managed with a version control system, this can cause problems
     * when files in that directory such as settings.php are updated, because the
     * user pulling in the changes won't have permissions to modify files in the
     * directory.
     */
    $settings['skip_permissions_hardening'] = TRUE;


    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    break;
}
