<?php

namespace Cnect\Robo;

use Robo\Tasks as RoboTasks;

/**
 * Class Tasks.
 *
 * @package Cnect\Robo\Task\Build
 */
class Tasks extends RoboTasks {
  use \Boedah\Robo\Task\Drush\loadTasks;
  use \NuvoleWeb\Robo\Task\Config\loadTasks;

  /**
   * Install site.
   *
   * @command project:install
   * @aliases pi
   */
  public function projectInstall() {
    $this
      ->getInstallTask()
      ->siteInstall('minimal')
      ->silent(TRUE)->printOutput(TRUE)
      ->run();
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

    if (!$options['force']) {
      // Check if the DB is empty.
      $db_tables = (int) $this->taskExec('mysql')
        ->option('user', $this->config('database.user'), '=')
        ->option('password', $this->config('database.password'), '=')
        ->option('host', $this->config('database.host'), '=')
        ->arg('--silent')
        ->arg('--raw')
        ->arg('--skip-column-names')
        ->option('execute', "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = \"{$this->config('database.name')}\"")
        ->silent(TRUE)
        ->printOutput(FALSE)
        ->run()
        ->getMessage();

      $is_installed = ($db_tables !== 0);
    }
    else {
      $is_installed = FALSE;
    }

    $gc = "\033[0;32m";
    $yc = "\033[0;33m";
    $wc = "\033[0m";

    $output = $is_installed
      ? "${yc} Drupal is already installed.\nUse --force to install anyway. ${wc}"
      : "${gc} Starting Drupal installation. ${wc}";

    $this->say($output);

    $settings_folder = "{$this->root()}/web/sites/default";
    $settings_file = "{$settings_folder}/settings.php";

    if (!file_exists($settings_file)) {
      $this->changeFilePerms("open");

      $this
        ->taskFlattenDir(['resources/files/*settings*php'])
        ->to($settings_folder)
        ->run();
    }
    else {
      $this->say("${yc} File settings.php already exists, skipping... ${wc}");
    }

    if (!$is_installed) {
      $this->getInstallTask()
        ->arg('--existing-config')
        ->siteInstall('minimal')
        ->silent(TRUE)->printOutput(TRUE)
        ->run();
    }
    $this->changeFilePerms("close");

    return TRUE;
  }

  /**
   * Set up custom config.
   *
   * @command project:set-custom-config
   * @aliases pscc
   */
  public function setCustomConfig() {
    $settings = $this->config('environment.settings');
    if (!empty($settings)) {

      $settings_folder = "{$this->root()}/web/sites/default";
      $settings_file = "$settings_folder/settings.local.php";

      $this->changeFilePerms("open");

      $this->taskWriteToFile($settings_file)->text("<?php\n")->run();
      if (!empty($settings)) {
        $this->recursive_print('$settings', $settings);
      }

      $this->changeFilePerms("close");
    }
    else {
      $this->say('No custom settings to add.');
    }
  }

  /**
   * Get installation task.
   */
  protected function getInstallTask() {
    return $this->taskDrushStack($this->config('bin.drush'))
      ->arg("--root={$this->root()}/web")
      ->siteName($this->config('site.name'))
      ->siteMail($this->config('site.mail'))
      ->locale($this->config('site.locale'))
      ->accountMail($this->config('account.mail'))
      ->accountName($this->config('account.name'))
      ->accountPass($this->config('account.password'))
      ->dbPrefix($this->config('database.prefix'))
      ->dbUrl(sprintf("mysql://%s:%s@%s:%s/%s",
        $this->config('database.user'),
        $this->config('database.password'),
        $this->config('database.host'),
        $this->config('database.port'),
        $this->config('database.name')));
  }

  /**
   * Get root directory.
   *
   * @return string
   *   Root directory.
   */
  protected function root() {
    return getcwd();
  }

  /**
   * Change folder/file permissions.
   */
  private function changeFilePerms($op = "open") {

    $settings_folder = "{$this->root()}/web/sites/default";
    $settings_files = "${settings_folder}/settings*.php";

    $perms = ($op == 'open')
      ? '0755'
      : '0644';

    if (file_exists("{$settings_folder}/settings.php")) {
      $this
        ->taskExec("chmod {$perms} {$settings_files}")
        ->silent(TRUE)
        ->printOutput(TRUE)
        ->run();
    }

    $this
      ->taskExec("chmod 0755 {$settings_folder}")
      ->silent(TRUE)
      ->printOutput(TRUE)
      ->run();

    $this
      ->taskExec("chmod -R 0755 {$settings_folder}/files")
      ->silent(TRUE)
      ->printOutput(TRUE)
      ->run();

  }

  /**
   * Helper to print settings arrays.
   */
  private function recursive_print($varname, $varval) {
    $path = $this->root() . '/web/sites/default/settings.local.php';
    if (!is_array($varval)) {
      $this->taskWriteToFile($path)->text($varname . " = \"" . $varval . "\";\n")
        ->append(true)
        ->run();
    }
    else {
      foreach ($varval as $key => $val) {
        $this->recursive_print ("$varname ['$key']", $val);
      }
    }
  }

}
