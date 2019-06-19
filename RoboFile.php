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
   * Install site from given configuration.
   *
   * @command project:install-config
   * @aliases pic
   *
   * @option $force Force the installation.
   */
  public function projectInstallConfig($options = ['force|f' => false]) {

    $is_installed = (!$options['force'])
      ? $this->isInstalled()
      : FALSE;

    !$is_installed || $options['force']
      ? $this->statusMessage("Starting Drupal installation.", "ok")
      : $this->statusMessage("Drupal is already installed.\n   Use --force to install anyway.", "warn");

    if (!$is_installed || $options['force']) {
      $this->getInstallTask()
        ->arg('--existing-config')
        ->siteInstall($this->env['SITE_PROFILE'])
        ->silent(TRUE)
        ->run();

      // Overwrite settings.php and settings.local.php.
      $this->rewriteSettings();
    }

    $this->statusMessage("Installation finished.", 'ok');

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
    $this->say('-------------------------------------------');
    $this->say($color . $text . $color_reset);
    $this->say('-------------------------------------------');
  }

  /**
   * Setup .env file.
   *
   * @command project:setup-env
   * @aliases pse
   */
  public function projectGenerateEnv(array $options = ['force' => FALSE, 'type' => NULL]) {
    $file = "{$this->projectRoot}/.env";
    if (!file_exists($file) || $options['force']) {
      $settings = [
        'ENVIRONMENT' => 'project.environment',
        'DATABASE_NAME' => 'database.name',
        'DATABASE_HOST' => 'database.host',
        'DATABASE_PORT' => 'database.port',
        'DATABASE_USERNAME' => 'database.user',
        'DATABASE_PASSWORD' => 'database.password',
        'DATABASE_PREFIX' => 'database.prefix',
        'SITE_PROFILE' => 'site.profile',
      ];
      $content = "";
      if ($options['type'] == 'docker') {
        $content .= "USER_ID=1000\n";
        $content .= "GROUP_ID=1000\n";
        $settings['DATABASE_ROOT_PASSWORD'] = 'database.root_password';
      }
      foreach ($settings as $key => $setting) {
        $content .= "$key={$this->config->get($setting)}\n";
      }
      if (!empty($content)) {
        $r = $this
          ->taskWriteToFile($file)
          ->text($content)
          ->run()
          ->getMessage();

        $this->statusMessage('Created .env file', 'ok');
      }
    }
    else {
      $this->statusMessage('File .env already exists, skipping...', 'warn');
    }
  }

  /**
   * Get hash_salt after install.
   */
  private function getHash() {
    $app_root = $this->projectRoot;
    $site_path = $this->drupalRoot;

    require_once $this->drupalRoot . '/core/includes/bootstrap.inc';
    require_once $this->drupalRoot . '/core/includes/install.inc';

    $file = $this->drupalRoot . '/sites/default/settings.php';
    require $file;
    return $settings['hash_salt'];
  }

  /**
   * Overwrite settings files.
   */
  private function rewriteSettings() {
    require_once $this->drupalRoot . '/core/includes/bootstrap.inc';
    require_once $this->drupalRoot . '/core/includes/install.inc';

    $hash = $this->getHash();
    if (!empty($hash)) {

      $source_folder = $this->projectRoot . '/resources/files';
      $target_folder = $this->drupalRoot . '/sites/default';

      // Unlock the sites/default folder and settings files.
      // Delete the settings files.
      $this->taskExecStack()
        ->exec("chmod ugo+w ${target_folder}")
        ->exec("chmod ugo+w ${target_folder}/settings.php")
        ->exec("chmod ugo+w ${target_folder}/settings.local.php")
        ->exec("rm ${target_folder}/settings.php")
        ->exec("rm ${target_folder}/settings.local.php")
        ->run();

      // Override the settings.file and lock it.
      $this->_copy($source_folder . '/settings.php', $target_folder . '/settings.php');
      $this->taskExec("chmod ugo-w ${target_folder}/settings.php")->run();

      // Place local settings file in place.
      // Place it in the shared folder if we're on AWS.
      $settings_folder = ($shared_folder = getenv("EFS_MOUNT_DIR"))
        ? $shared_folder
        : $target_folder;

      $this->_copy($source_folder . '/settings.local.php', $settings_folder . '/settings.local.php');
      $this->taskWriteToFile($settings_folder . '/settings.local.php')
        ->append(true)
        ->text("\n\$settings['hash_salt'] = '{$hash}';\n")
        ->run();

      $this->taskExec("chmod ugo-w ${settings_folder}/settings.local.php")->run();

      // Lock the sites default folder.
      $this->taskExec("chmod ugo-w ${target_folder}")->run();
    }
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
   * Istall theme.
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

}
