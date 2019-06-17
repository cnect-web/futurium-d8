<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
use Robo\Tasks as RoboTasks;
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

    $dotenv = new Dotenv($this->projectRoot);
    $dotenv->load();
    $this->env = getenv();
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
        ->run();
    }

    return TRUE;

  }

  /**
   * Get installation task.
   */
  protected function getInstallTask() {
    return $this->taskDrushStack($this->binDir . '/drush')
      ->arg("--root={$this->drupalRoot}")
      ->dbPrefix($this->env['DATABASE_PREFIX'])
      ->dbUrl(sprintf("mysql://%s:%s@%s:%s/%s",
        $this->env['DATABASE_USERNAME'],
        $this->env['DATABASE_PASSWORD'],
        $this->env['DATABASE_HOST'],
        $this->env['DATABASE_PORT'],
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

}
