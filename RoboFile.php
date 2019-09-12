<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Robo\Tasks as RoboTasks;
use Robo\Config\Config;
use Robo\Contract\TaskInterface;
use Consolidation\Config\Loader\YamlConfigLoader;
use Consolidation\Config\Loader\ConfigProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
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
    $binDir = Path::makeRelative("{$drupalFinder->getVendorDir()}/bin", $projectRoot);

    $this->projectRoot = $projectRoot;
    $this->drupalRoot = $drupalRoot;
    $this->binDir = $binDir;

    if (file_exists("{$projectRoot}/.env")) {
      $dotenv = new Dotenv($this->projectRoot);
      $dotenv->load();
      $this->env = getenv();
    }
  }

  /**
   * Add default options.
   *
   * @param \Symfony\Component\Console\Command\Command $command
   *   Command object.
   *
   * @hook option
   */
  public function defaultOptions(Command $command) {
    $command->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Configuration file to be used instead of default `robo.yml.dist`.', 'robo.yml');
    $command->addOption('override', 'o', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Configuration value(s) to be overridden. Format: "path.to.key:value"', []);
  }

  /**
   * Command initialization.
   *
   * @param \Symfony\Component\Console\Input\Input $input
   *   Input object.
   *
   * @hook init
   */
  public function initializeConfiguration(Input $input) {
    // Initialize configuration objects.
    $config = new Config();
    $loader = new YamlConfigLoader();
    $processor = new ConfigProcessor();
    // Extend and import configuration.
    $processor->add($config->export());
    $processor->extend($loader->load('config.yml.dist'));
    $processor->extend($loader->load('config.yml'));
    $processor->extend($loader->load($input->getOption('config')));

    // Replace tokens in final configuration file.
    $export = $processor->export();
    // Load configuration with unprocessed tokens just to be able to access
    // the static values as dot chained names.
    $config->import($export);
    array_walk_recursive($export, function (&$value, $key) use ($config) {
      if (is_string($value)) {
        preg_match_all('/![A-Za-z_\-.]+/', $value, $matches);
        foreach ($matches[0] as $match) {
          $config_key = substr($match, 1, strlen($match));
          $value = str_replace($match, $config->get($config_key), $value);
        }
      }
    });
    // Reimport the config, this time with the tokens replaced.
    $config->import($export);
    // Process command line overrides.
    foreach ($input->getOption('override') as $override) {
      $override = (array) Yaml::parse($override);
      $override_key = key($override);
      $override_value = array_shift($override);
      if (!empty($override_value)) {
        $config->set($override_key, $override_value);
      }
      else {
        $this->statusMessage("Empty override value: \"{$override_key}\", using default value instead.", 'warn');
      }
    };
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
    $drupalRoot = $this->drupalRoot;

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    // Required for unit testing
    foreach ($dirs as $dir) {
      $directoryPath = "$drupalRoot/$dir";
      if (!$fs->exists($directoryPath)) {
        $fs->mkdir($directoryPath);
        $fs->touch("$directoryPath/.gitkeep");
        $this->say("Created $directoryPath .");
      }
    }

    // Prepare the settings file for installation.
    if (!$fs->exists($this->getSiteSettingsFile()) and $fs->exists($this->getResourceSettingsFile())) {
      $fs->chmod($this->getSiteDefaultFolder(), 0775);
      $fs->copy($this->getResourceSettingsFile(), $this->getSiteSettingsFile());
      $fs->chmod($this->getSiteSettingsFile(), 0666);
      $this->say("Created {$this->getSiteSettingsFile()}");
    }

    $fs->chmod($this->getSiteDefaultFolder(), 0755);

    // Create the files directory with chmod 0777
    if (!$fs->exists($this->getSiteDefaultFilesFolder())) {
      $oldmask = umask(0);
      $fs->mkdir($this->getSiteDefaultFilesFolder(), 0777);
      umask($oldmask);
      $this->say("Created a {$this->getSiteDefaultFilesFolder()} directory with chmod 0777");
    }
  }

  /**
   * Overwrites the settings files with symlinks if they exist.
   *
   * @command project:init-symlinks
   * @aliases pisl
   */
  public function initSymLinks() {
    // We only want to check this on AWS.
    if ($this->isAws()) {
      $fs = $this->fs;

      $drupal_settings_folder = $this->getSiteDefaultFolder();
      $shared_folder = $this->getLocalSettingsFolder();

      $files = ['settings.php', 'settings.local.php'];
      $fs->chmod($drupal_settings_folder, 0777);

      // Check if settings file exist on nfs mount.
      // If a shared settings file exists, use that.
      foreach ($files as $file) {
        $this->symlinkFolders("${shared_folder}/${file}", "$drupal_settings_folder/$file");
      }

      // Create the symlink to the files folder.
      if (file_exists("$shared_folder/files")) {
        $this->symlinkFolders("$shared_folder/files", "$drupal_settings_folder/files");
      }

      // Lock the sites/default folder.
      $fs->chmod($drupal_settings_folder, 0555);
    }
  }

  protected function symlinkFolders($existing_folder, $folder) {
    if (file_exists($existing_folder)) {
      // If a settings file exists in the sites default, delete it.
      if (file_exists($folder) && !is_link($folder)) {
        $this->fs->chmod($folder, 0777);
        $this->fs->remove($folder);
      }

      $this->_symlink($existing_folder, $folder);
    }
  }

  /**
   * Installs or updates site if already installed.
   *
   * @command project:install-update
   * @aliases piu
   */
  public function projectInstallOrUpdate($options = ['force' => FALSE]) {

    // Initialize the filesystem.
    $this->initFileSystem();

    // If the website is installed and we're not forcing the install,
    // just import the config.
    (!$this->isInstalled() || $options['force'])
      ? $this->installConfig($options)
      : $this->importConfig();

    // Initialize the symlinks.
    $this->initSymLinks();
  }

  /**
   * Install site from given configuration.
   *
   * @command project:install-config
   * @aliases pic
   *
   * @option $force Force the installation.
   */
  public function installConfig($options = ['force' => FALSE]) {
    $fs = $this->fs;

    $is_installed = (!$options['force'])
      ? $this->isInstalled()
      : FALSE;

    if (!$is_installed || $options['force']) {

      // Delete local.settings.php if it exists before install.
      $local_settings_file = $this->getLocalSettingsLocalFile();
      if ($fs->exists($local_settings_file)) {
        $fs->chmod($local_settings_file, 0777);
        $fs->remove($local_settings_file);
        $this->say("Deleted $local_settings_file file.");
      }

      $this->statusMessage('Starting Drupal installation.', 'ok');
      $this->getInstallTask()
        ->arg('--existing-config')
        ->siteInstall($this->config->get('site.profile'))
        ->silent(TRUE)
        ->run();

      // Rewrite the settings.
      $this->rewriteSettings();

      $this->statusMessage('Installation finished.', 'ok');
    }
    else {
      $this->statusMessage("Drupal is already installed.\n   Use --force to install anyway.",'warn');
    }

    // On installation, drupal only takes into account config in the config/sync dir.
    // So we need to import config so that config splits kick in.
    $this->importConfig();
  }

  /**
   * Get installation task.
   */
  protected function getInstallTask() {
    return $this->taskDrushStack($this->getDrushPath())
      ->arg("--root={$this->drupalRoot}")
      ->accountName($this->config->get('account.name'))
      ->accountMail($this->config->get('account.mail'))
      ->accountPass($this->config->get('account.password'))
      ->dbPrefix($this->config->get('database.prefix'))
      ->dbUrl(sprintf('mysql://%s:%s@%s:%s/%s',
        $this->env['DATABASE_USERNAME'],
        urlencode($this->env['DATABASE_PASSWORD']),
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
    $this->taskDrushStack($this->getDrushPath())
      ->exec('cr')
      ->exec('cache-clear drush')
      ->exec('updb')
      ->exec('cim -y')
      ->exec('cr')
      ->silent(TRUE)
      ->run();
  }

  /**
   * Export config from database to filesystem.
   *
   * @command project:export-config
   * @aliases exc
   */
  public function exportConfig() {
    $this->taskDrushStack($this->getDrushPath())
      ->exec('cr')
      ->exec('cache-clear drush')
      ->exec('cex -y')
      ->exec('cr')
      ->run();
  }

  /**
   * Is Drupal instance installed.
   */
  protected function isInstalled() {

    // Ensure the symlinks are in place.
    $this->initSymlinks();

    // Check if the DB is empty.
    $db_tables = (int) $this->taskExec('mysql')
      ->option('user', $this->env['DATABASE_USERNAME'], '=')
      ->option('password', $this->env['DATABASE_PASSWORD'], '=')
      ->option('host', $this->env['DATABASE_HOST'], '=')
      ->arg('--silent')
      ->arg('--raw')
      ->arg('--skip-column-names')
      ->option('execute',
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \"{$this->env['DATABASE_NAME']}\"")
      ->silent(TRUE)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();

    return ($db_tables !== 0);
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

      $content = '';

      if ($options['type'] == 'docker') {
        $content .= "USER_ID={$this->config->get('id.user')}\n";
        $content .= "GROUP_ID={$this->config->get('id.group')}\n";
        $settings['DATABASE_ROOT_PASSWORD'] = 'database.root_password';
      }

      foreach ($settings as $key => $setting) {
        // We need the env vars on docker and other local environments.
        if (!getenv('EFS_MOUNT_DIR')) {
          $content .= "$key={$this->config->get($setting)}\n";
        }
        // Don't override existing environment variables on aws.
        elseif (getenv('EFS_MOUNT_DIR') && !getenv($key)) {
          $content .= "$key={$this->config->get($setting)}\n";
        }
        else {
          $this->statusMessage("Environment variable \"${key}\" already exists, skipping...",
            'warn');
        }
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
   * Rewrite settings files.
   *
   * @command project:rewrite-settings
   * @aliases rs
   */
  public function rewriteSettings() {
    require_once "{$this->drupalRoot}/core/includes/bootstrap.inc";
    require_once "{$this->drupalRoot}/core/includes/install.inc";

    $settings_file = $this->getLocalSettingsFile();
    $local_settings_file = $this->getLocalSettingsLocalFile();

    // Unlock the sites/default folder and settings file.
    $this->fs->chmod($this->getSiteDefaultFolder(), 0775);
    $this->fs->chmod($this->getSiteSettingsFile(), 0775);
    if (file_exists($local_settings_file)) {
      $this->fs->chmod($local_settings_file, 0777);
    }

    // Initialize Settings.
    Settings::initialize($this->drupalRoot, 'sites/default', $this->classLoader);
    $hash = Settings::get('hash_salt');

    if (!empty($hash)) {
      // Overwrite the settings.file.
      $this->fs->remove($this->getSiteSettingsFile());
      $this->_copy($this->getResourceSettingsFile(), $settings_file);

      // Re-add the hash_salt to settings.php
      $settings['settings']['hash_salt'] = (object) [
        'value' => $hash,
        'required' => TRUE,
      ];

      $this->fs->chmod($settings_file, 0777);
      drupal_rewrite_settings($settings, $settings_file);
    }

    // Write local settings if a file doesn't exist.
    if (!file_exists($local_settings_file)) {
      $this->_copy($this->getResourceLocalSettingsFile(), $local_settings_file);
      // Add the hash_salt copied from settings.php to settings.local.php
      if (!empty($settings)) {
        $this->fs->chmod($local_settings_file, 0777);
        drupal_rewrite_settings($settings, $local_settings_file);
      }
      // Write additional environment settings.
      $this->setCustomConfig();
    }

    // Reset the permissions to the proper state.
    $this->fs->chmod($this->getSiteDefaultFolder(), 0555);
    $this->fs->chmod($settings_file, 0444);
    $this->fs->chmod($local_settings_file, 0444);
  }

  protected function isAws() {
    return getenv('EFS_MOUNT_DIR') !== FALSE;
  }

  // Define a place for the local settings file.
  // If we're on AWS, place it in the shared folder.
  // Otherwise just place it in the normal location (sites/default).
  protected function getLocalSettingsFolder() {
    if ($this->isAws()) {
      return getenv('EFS_MOUNT_DIR');
    }
    return $this->getSiteDefaultFolder();
  }

  /**
   * Install theme.
   *
   * @command theme:install
   * @aliases ti
   */
  public function themeInstall() {
    $path = $this->getThemePath();
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
    $this->taskExec('npm')
      ->arg('run')
      ->arg('watch')
      ->dir($this->getThemePath())
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
      $result = $this->codeSniff($paths);
      if (!$result->wasSuccessful()) {
        return $result;
      }
    }

    if (in_array('cb', $op)) {
      $result = $this->codeBeautifier($paths);
      if (!$result->wasSuccessful()) {
        return $result;
      }
    }

    if (in_array('unit', $op)) {
      $result = $this->ut($paths);
      if (!$result->wasSuccessful()) {
        return $result;
      }
    }

    if (in_array('behat', $op)) {
      $result = $this->behat($paths);
      if (!$result->wasSuccessful()) {
        return $result;
      }
    }

    return $result;
  }

  /**
   * Run unit tests.
   *
   * @command tests:ut
   * @aliases ut
   */
  public function ut(array $paths) {
    return $this->taskExec('php web/core/scripts/run-tests.sh --color --keep-results --suppress-deprecations --concurrency "36" --repeat "1" --directory ' . implode(' ', $paths) . ' PHPUnit')
      ->run();
  }

  /**
   * Run code sniffer.
   *
   * @command tests:code-sniff
   * @aliases tcs
   */
  public function codeSniff(array $paths) {
    return $this
      ->taskExec('bin/phpcs --standard=phpcs-ruleset.xml ' . implode(' ', $paths))
      ->run();
  }

  /**
   * Run Behat tests.
   *
   * @command tests:behat
   * @aliases tbt
   */
  public function behat() {

    $command = 'bin/behat';
    if ($config = $this->config->get('behat.config')) {
      $command .= ' -c ' . $config;
    }

    if ($tags = $this->config->get('behat.tags')) {
      $command .= ' --tags=' . $tags;
    }

    return $this
      ->taskExec($command)
      ->run();
  }

  /**
   * Run phpcbf.
   *
   * @command tools:cb
   * @aliases tcb
   */
  public function codeBeautifier(array $paths) {
    return $this
      ->taskExec('bin/phpcbf --standard=phpcs-ruleset.xml ' . implode(' ', $paths))
      ->run();
  }

  /**
   * Login user.
   *
   * @param array $options
   */
  public function userLogin($options = ['uid|u' => '1']) {
    $loginUrl = $this->taskExec($this->getDrushPath())
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

  /**
   * Rebuild cache.
   *
   * @throws \Robo\Exception\TaskException
   */
  public function cacheRebuild() {
    $this->taskDrushStack($this->getDrushPath())
      ->drush('cache-rebuild')
      ->arg("--root={$this->drupalRoot}")
      ->silent(TRUE)
      ->run();
  }

  /**
   * Create a release ready artifact.
   *
   * @command release:package
   * @aliases rp
   *
   * @option $force Force the installation.
   */
  public function releasePackage($archive_name = NULL) {
    $this->_exec("./resources/scripts/deploy/package.sh ${archive_name}");
  }

  /**
   * Set up custom config.
   *
   * @command project:set-custom-config
   * @aliases pscc
   */
  public function setCustomConfig($target_file = 'settings.local.php') {
    $environment = getenv('ENVIRONMENT') ?? $this->config->get('project.environment');
    $settings['settings'] = $this->config->get("environment.$environment.settings");
    if (!empty($settings['settings'])) {

      require_once "{$this->drupalRoot}/core/includes/bootstrap.inc";
      require_once "{$this->drupalRoot}/core/includes/install.inc";

      $settings_folder = $this->getLocalSettingsFolder();

      // Initialize Settings.
      Settings::initialize($this->drupalRoot, $settings_folder,$this->classLoader);
      foreach ($settings['settings'] as $key => $setting) {
        if (is_array($setting)) {
          foreach ($setting as $k => $v) {
            $settings['settings'][$key][$k] = (object) [
              'value' => $v,
              'required' => TRUE,
            ];
          }
        }
        else {
          $settings['settings'][$key] = (object) [
            'value' => $setting,
            'required' => TRUE,
          ];
        }
      }

      drupal_rewrite_settings($settings, "$settings_folder/$target_file");
    }
    else {
      $this->say('No custom settings to add.');
    }
  }

  /**
   * Print a prettiefied message.
   */
  protected function statusMessage($text, $type) {
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
   * Gets Drush path.
   *
   * @return string
   *   Drush path.
   */
  protected function getDrushPath() {
    return "{$this->binDir}/drush";
  }

  /**
   * Gets the theme path.
   *
   * @return string
   */
  protected function getThemePath() {
    return "{$this->drupalRoot}/{$this->config->get('theme.path')}";
  }

  /**
   * Gets site default folder.
   *
   * @return string
   */
  protected function getSiteDefaultFolder() {
    return "{$this->drupalRoot}/sites/default";
  }

  /**
   * Gets the default files folder.
   *
   * @return string
   */
  protected function getSiteDefaultFilesFolder() {
    return "{$this->getSiteDefaultFolder()}/files";
  }

  /**
   * Gets resource files folder.
   *
   * @return string
   */
  protected function getResourceFilesFolder() {
    return "{$this->projectRoot}/resources/files";
  }

  /**
   * @return string
   */
  protected function getResourceSettingsFile() {
    return "{$this->getResourceFilesFolder()}/settings.php";
  }

  /**
   * Gets settings.local.php file for the default folder.
   *
   * @return string
   */
  protected function getResourceLocalSettingsFile() {
    return "{$this->getResourceFilesFolder()}/settings.local.php";
  }

  /**
   * Gets settings.php file for the default folder.
   *
   * @return string
   */
  protected function getSiteSettingsFile() {
    return "{$this->getSiteDefaultFolder()}/settings.php";
  }

  /**
   * Gets settings.local.php file for the local settings folder.
   *
   * @return string
   */
  protected function getLocalSettingsLocalFile() {
    return "{$this->getLocalSettingsFolder()}/settings.local.php";
  }

  /**
   * Gets settings.php file for the local settings folder.
   *
   * @return string
   */
  protected function getLocalSettingsFile() {
    return "{$this->getLocalSettingsFolder()}/settings.php";
  }

}
