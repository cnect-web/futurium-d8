<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Robo\Tasks as RoboTasks;
use Robo\Config\Config;
use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Loader\ConfigProcessor;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;
use Dotenv\Dotenv;
use Drupal\Core\Site\Settings;

/**
 * Class RoboFile.
 */
class RoboFile extends RoboTasks {
  use \Boedah\Robo\Task\Drush\loadTasks;

  private $env;
  private $fs;

  private $binDir;
  private $projectRoot;
  private $drupalRoot;

  private $config;

  private $defaultOp = 'cs,unit,behat';
  private $defaultPaths = 'web/modules/custom,web/themes/contrib/blellow';

  /**
   * Constructor.
   */
  public function __construct() {

    $this->fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());

    $projectRoot = $drupalFinder->getComposerRoot();
    $drupalRoot = Path::makeRelative($drupalFinder->getDrupalRoot(), $projectRoot);
    $binDir = Path::makeRelative($drupalFinder->getVendorDir() . '/bin', $projectRoot);

    $this->projectRoot = $projectRoot;
    $this->drupalRoot = $drupalRoot;
    $this->binDir = $binDir;

    if (file_exists("{$projectRoot}/.env")) {
      $dotenv = new Dotenv($this->projectRoot);
      $dotenv->load();
      $this->env = getenv();
    }

    $config = new Config();
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();
    $processor->extend($loader->load($this->projectRoot . '/config.yml'));
    $config->import($processor->export());
    $this->config = $config;

  }

  /**
   * Creates the files and folders needed to install drupal.
   * Copied from ScriptHandler.
   *
   * @command project:init-filesystem
   * @aliases pifs
   */
  public function initFileSystem() {
    $fs = $this->fs;
    $projectRoot = $this->projectRoot;
    $drupalRoot = $this->drupalRoot;

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
        $this->say("Created ${drupalRoot}/${dir} .");
      }
    }

    // Prepare the settings file for installation
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php') and $fs->exists($projectRoot . '/resources/files/settings.php')) {
      $fs->chmod($drupalRoot . '/sites/default', 0775);
      $fs->copy($projectRoot . '/resources/files/settings.php', $drupalRoot . '/sites/default/settings.php');
      $fs->chmod($drupalRoot . '/sites/default/settings.php', 0666);
      $this->say("Created sites/default/settings.php");
    }

    $fs->chmod($drupalRoot . '/sites/default', 0755);

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      $this->say("Created sites/default/files/");
    }
  }

  /**
   * Install site from given configuration.
   *
   * @command project:install-update
   * @aliases piu
   */
  public function projectInstallOrUpdate() {
    ($this->isInstalled())
      ? $this->importConfig()
      : $this->projectInstallConfig();
  }

  /**
   * Install site from given configuration.
   *
   * @command project:install-config
   * @aliases pic
   *
   * @option $force Force the installation.
   */
  public function installConfig($options = ['force|f' => false]) {

    $fs = $this->fs;
    $drupalRoot = $this->drupalRoot;

    $this->initFileSystem();

    $is_installed = (!$options['force'])
      ? $this->isInstalled()
      : FALSE;

    if (!$is_installed || $options['force']) {

      // Delete local.settings.php if it exists.
      if ($fs->exists($drupalRoot . '/sites/default/settings.local.php')) {
        $fs->chmod($drupalRoot . '/sites/default', 0777);
        $fs->chmod($drupalRoot . '/sites/default/settings.local.php', 0777);
        $fs->remove($drupalRoot . '/sites/default/settings.local.php');
        $this->say("Deleted sites/default/settings.local.php file.");
      }

      $this->statusMessage("Starting Drupal installation.", "ok");
      $this->getInstallTask()
        ->arg('--existing-config')
        ->siteInstall($this->config->get('site.profile'))
        ->silent(TRUE)
        ->run();

      $this->rewriteSettings();

      $this->statusMessage("Installation finished.", 'ok');
    }
    else $this->statusMessage("Drupal is already installed.\n   Use --force to install anyway.", "warn");

    return TRUE;
  }

  /**
   * Get installation task.
   */
  protected function getInstallTask() {
    return $this->taskDrushStack($this->binDir . '/drush')
      ->arg("--root={$this->drupalRoot}")
      ->accountName($this->config->get('account.name'))
      ->accountMail($this->config->get('account.mail'))
      ->accountPass($this->config->get('account.password'))
      ->dbPrefix($this->config->get('database.prefix'))
      ->dbUrl(sprintf("mysql://%s:%s@%s:%s/%s",
        $this->env['DATABASE_USERNAME'],
        $this->env['DATABASE_PASSWORD'],
        $this->env['DATABASE_HOST'],
        $this->config->get('database.port'),
        $this->env['DATABASE_NAME']));
  }

  /**
   * Import config from filesystem to database.
   *
   * @command project:import-config
   * @aliases imc
   */
  public function importConfig() {
    $this->taskDrushStack($this->binDir . '/drush')
      ->arg('-r', $this->drupalRoot)
      ->exec('cache-clear drush')
      ->exec('updb')
      ->exec('csim -y')
      ->exec('cr')
      ->run();
  }

  /**
   * Export config from database to filesystem.
   *
   * @command project:export-config
   * @aliases exc
   */
  public function exportConfig() {
    $this->taskDrushStack($this->binDir . '/drush')
      ->arg('-r', $this->drupalRoot)
      ->exec('cache-clear drush')
      ->exec('csex -y')
      ->exec('cr')
      ->run();
  }

  /**
   * Run QA tasks.
   *
   * @command tools:qa
   * @aliases qa
   *
   * Usage:
   * qa -p web/modules/custom -z cs
   * qa -p path1,path2 -z cs,unit
   */
  public function qa(array $options = ['path|p' => '', 'op|z' => '']) {

    if (empty($options['path'])) {
      $options['path'] = $this->defaultPaths;
    }

    if (empty($options['op'])) {
      $options['op'] = $this->defaultOp;
    }

    $op = explode(',', $options['op']);
    $paths = explode(',', $options['path']);

    if (in_array('cs', $op)) {
      $this->say('Running code sniffer...');
      $this->cs($paths);
    }
    if (in_array('unit', $op)) {
      $this->say('Running unit tests...');
      $this->put($paths);
    }

    if (in_array('behat', $op)) {
      $this->say('Running behat tests...');
      $this->behat($paths);
    }

  }

  /**
   * Run unit tests.
   *
   * @command tools:put
   * @aliases put
   */
  public function put(array $paths) {
    $this
      ->taskExec('sudo php ./bin/run-tests.sh --color --keep-results --suppress-deprecations --types "Simpletest,PHPUnit-Unit,PHPUnit-Kernel,PHPUnit-Functional" --concurrency "36" --repeat "1" --directory ' . implode(' ', $paths))
      ->run();
  }

  /**
   * Run code sniffer.
   *
   * @command tools:code-sniff
   * @aliases cs
   */
  public function cs(array $paths) {
    if ($this
      ->taskExec('bin/phpcs --standard=phpcs-ruleset.xml ' . implode(' ', $paths))
      ->run()
      ->wasSuccessful()
    ) {
      $this->say('Code sniffer finished.');
    };
  }

  /**
   * Run Behat tests.
   *
   * @command tools:behat
   * @aliases bt
   */
  public function behat() {
    if ($this
      ->taskExec('bin/behat -c tests/behat.yml')
      ->run()
      ->wasSuccessful()
    ) {
      $this->say('Behat finished.');
    };
  }

  /**
   *
   */
  private function isInstalled() {
    // Check if the DB is empty.
    $db_tables = (int) $this->taskExec('mysql')
      ->option('user', $this->env['DATABASE_USERNAME'], '=')
      ->option('password', $this->env['DATABASE_PASSWORD'], '=')
      ->option('host', $this->env['DATABASE_HOST'], '=')
      ->arg('--silent')
      ->arg('--raw')
      ->arg('--skip-column-names')
      ->option('execute', "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \"{$this->env['DATABASE_NAME']}\"")
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    return ($db_tables !== 0);
  }

  /**
   *
   */
  private function statusMessage($text, $type) {
    $color_reset = "\033[0m";
    switch ($type) {

      case 'ok':
        $color = "\e[32m";
        break;

      case 'warn':
        $color = "\e[33m";
        break;

      case 'error':
        $color = "\e[31m";
        break;
    }
    $this->say($color . $text . $color_reset);
  }

  /**
   * Setup .env file.
   *
   * @command project:setup-env
   * @aliases pse
   */
  public function projectGenerateEnv(array $options = ['force' => FALSE, 'type' => NULL]) {
    $file = "{$this->projectRoot}/.env";
    if (!file_exists($file) && !$options['force']) {

      $settings = [
        'ENVIRONMENT' => 'project.environment',
        'DATABASE_NAME' => 'database.name',
        'DATABASE_HOST' => 'database.host',
        'DATABASE_PORT' => 'database.port',
        'DATABASE_USERNAME' => 'database.user',
        'DATABASE_PASSWORD' => 'database.password',
        'DATABASE_PREFIX' => 'database.prefix',
      ];

      $content = "";

      if ($options['type'] == 'docker') {
        $content .= "USER_ID=1000\n";
        $content .= "GROUP_ID=1000\n";
        $settings['DATABASE_ROOT_PASSWORD'] = 'database.root_password';
      }

      foreach ($settings as $key => $setting) {
        // We need the env vars on docker and other local environments.
        if (!getenv('EFS_MOUNT_DIR'))
          $content .= "$key={$this->config->get($setting)}\n";
        // Don't override existing environment variables on aws.
        elseif (getenv('EFS_MOUNT_DIR') && !getenv($key))
          $content .= "$key={$this->config->get($setting)}\n";
        else
          $this->statusMessage("Environment variable \"${key}\" already exists, skipping...", "warn");
      }
      if (!empty($content)) {
        $this->taskWriteToFile($file)->text($content)->run()->getMessage();
        $this->statusMessage('.env file created.', 'ok');
      }
    }
    else {
      $this->statusMessage('.env file already exists, skipping...', 'warn');
    }
  }

  /**
   * Overwrite settings files.
   */
  public function rewriteSettings() {
    require_once $this->drupalRoot . '/core/includes/bootstrap.inc';
    require_once $this->drupalRoot . '/core/includes/install.inc';

    $source_folder = $this->projectRoot . '/resources/files';
    $target_folder = $this->drupalRoot . '/sites/default';

    // Define a place for the local settings file.
    // If we're on AWS, place it in the shared folder.
    // Otherwise just place it in the normal location (sites/default).
    $settings_folder = ($shared_folder = getenv("EFS_MOUNT_DIR"))
      ? $shared_folder
      : $target_folder;

    // Unlock the sites/default folder and settings file.
    $this->fs->chmod($this->drupalRoot . '/sites/default', 0775);
    $this->fs->chmod($this->drupalRoot . '/sites/default/settings.php', 0775);

    // Initialize Settings.
    Settings::initialize($this->drupalRoot, 'sites/default', $this->classLoader);
    $hash = Settings::get('hash_salt');

    if (!empty($hash)) {

      // Overwrite the settings.file.
      $this->fs->remove($this->drupalRoot . '/sites/default/settings.php');
      $this->_copy($source_folder . '/settings.php', $target_folder . '/settings.php');

      // Re-add the hash_salt to settings.php
      $settings['settings']['hash_salt'] = (object) [
        'value'    => $hash,
        'required' => TRUE,
      ];

      drupal_rewrite_settings($settings, $this->drupalRoot . '/sites/default/settings.php');

      // Don't overwrite local settings if a file already exists.
      if (!file_exists($settings_folder . '/settings.local.php')) {
        $this->_copy($source_folder . '/settings.local.php', $settings_folder . '/settings.local.php');
      }

      // Add the hash_salt copied from settings.php to settings.local.php
      drupal_rewrite_settings($settings, $this->drupalRoot . '/sites/default/settings.local.php');
    }

    // Reset the permissions to the proper state.
    $this->fs->chmod($this->drupalRoot . '/sites/default', 0555);
    $this->fs->chmod($this->drupalRoot . '/sites/default/settings.php', 0444);
    $this->fs->chmod($settings_folder. '/settings.local.php', 0444);

    return TRUE;
  }

  /**
   * Overwrite theme for dev purposes.
   *
   * @command theme:download
   * @aliases td
   *
   * @option $watch Start the watcher after installation.
   */
  public function themeDownload($options = ['watch|w' => FALSE]) {
    $repo = $this->config->get('theme.dev.repo');
    $path = $this->drupalRoot . '/' . $this->config->get('theme.path');
    $branch = $this->config->get('theme.dev.branch');
    $this->taskExec("rm -rf ${path}")->run();

    $this->taskGitStack()
      ->cloneRepo($repo, $path, $branch)
      ->run();

    $this->themeInstall();

    if ($options['watch']) {
      $this->themeWatch();
    }
  }

  /**
   * Install theme.
   *
   * @command theme:install
   * @aliases ti
   */
  public function themeInstall() {
    $path = $this->drupalRoot . '/' . $this->config->get('theme.path');
    $this->taskNpmInstall()
      ->dir($path)
      ->run();

    $this->taskExec('npm')
      ->arg('run')
      ->arg('build')
      ->dir($path)
      ->run();

    $this->cacheRebuild();

  }

  /**
   * Start theme watcher.
   *
   * @command theme:watch
   * @aliases tw
   */
  public function themeWatch() {
    $path = $this->drupalRoot . '/' . $this->config->get('theme.path');
    $this->taskExec('npm')
      ->arg('run')
      ->arg('watch')
      ->dir($path)
      ->run();
  }

  public function userLogin($options = ['uid|u' => "1"]) {
    $loginUrl = $this->taskExec($this->binDir . '/drush')
      ->arg('uli')
      ->option('--uri', $this->config->get('project.url'), '=')
      ->option('--root', $this->drupalRoot, '=')
      ->option('--uid', $options['uid'], '=')
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    $this->say($loginUrl);

  }

  public function cacheRebuild() {
    $this->taskDrushStack($this->binDir . '/drush')
      ->drush('cache-rebuild')
      ->arg("--root={$this->drupalRoot}")
      ->silent(TRUE)
      ->run();
  }

}


