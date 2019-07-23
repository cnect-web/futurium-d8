<?php

namespace fut\Robo;

use Robo\Robo;
use Robo\Tasks as RoboTasks;

/**
 * Class Tasks.
 *
 * @package fut\Robo\Task\Build
 */
class Tasks extends RoboTasks {
  use \Boedah\Robo\Task\Drush\loadTasks;
  use \NuvoleWeb\Robo\Task\Config\loadTasks;

  /**
   * Output hostname.
   */
  public function ec2Hostname() {
    $result = $this
      ->taskExec('curl http://169.254.169.254/latest/meta-data/public-hostname')
      ->printOutput(false)
      ->run();
    if($result->wasSuccessful()) {
      $hostname = $result->getMessage();
      $this->say($hostname);
    }
  }

  /**
   * Output ip.
   */
  public function ec2Ip() {
    $result = $this
      ->taskExec('curl http://169.254.169.254/latest/meta-data/local-ipv4')
      ->printOutput(false)
      ->run();

    if($result->wasSuccessful()) {
      $ip = $result->getMessage();
      $this->say($ip);
    }
  }

  /**
   * Setup Behat.
   *
   * @command project:setup-behat
   * @aliases psb
   */
  public function projectSetupBehat() {
    $behat_tokens = $this->config('behat.tokens');

    $this->collectionBuilder()->addTaskList([
      $this->taskFilesystemStack()
        ->copy($this->config('behat.source'), $this->config('behat.destination'), TRUE),
      $this->taskReplaceInFile($this->config('behat.destination'))
        ->from(array_keys($behat_tokens))
        ->to($behat_tokens),
      $this->taskReplaceInFile($this->config('behat.destination'))
        ->from("{drupal_root}")
        ->to($this->config('project.root')),
      $this->taskReplaceInFile($this->config('behat.destination'))
        ->from("{base_url}")
        ->to($this->config('project.url')),
    ])->run();
  }

  /**
   * Install site.
   *
   * @command project:install
   * @aliases pi
   */
  public function projectInstall() {
    $this->npm();
    $this->projectGenerateEnv();
    $this->getInstallTask()
      ->siteInstall($this->config('site.profile'))
      ->run();
  }

  /**
   * Install site from given configuration.
   *
   * @command project:install-config
   * @aliases pic
   *
   * @option $run-importers Run the importers after installation.
   */
  public function projectInstallConfig(array $opts = ['run-importers|i' => FALSE]) {
    $this->npm();
    $this->projectGenerateEnv();

    $this->getInstallTask()
      ->arg('--existing-config')
      ->siteInstall('minimal')
      ->run();

    // Run config import in order to apply config split settings.
    $this->importConfig();
    // Change folder permissions.
    // @todo: get folder from config.
    $this->setupFilesFolder();
  }

  /**
   * Setup .env file.
   *
   * @command project:setup-env
   * @aliases pse
   */
  public function projectGenerateEnv(array $opts = ['force' => FALSE]) {
    $file = "{$this->root()}/.env";
    if (!file_exists($file) || $opts['force']) {
      $content = '';
      $settings = [
        'ENVIRONMENT' => 'project.environment',
        'DATABASE' => 'database.name',
        'DATABASE_HOST' => 'database.host',
        'DATABASE_PORT' => 'database.port',
        'DATABASE_USER' => 'database.user',
        'DATABASE_PASSWORD' => 'database.password',
        'DATABASE_PREFIX' => 'database.prefix',
      ];
      foreach ($settings as $key => $setting) {
        $content .= "$key={$this->config($setting)}\n";
      }
      if (!empty($content)) {
        $this->taskWriteToFile($file)->text($content)->run();
      }
    }
    else {
      $this->say('File exists, skipping...');
    }
  }

  /**
   * Setup files folder.
   *
   * @command project:setup-files-folder
   * @aliases sff
   */
  public function setupFilesFolder($folder = "web/sites/default/files") {
    if ($this->taskExec("rm -rf $folder/*")->run()->wasSuccessful()) {
      $this->say('Cleared up files folder.');
    }

    if ($this->taskExec("chmod -R 0777 $folder")->run()->wasSuccessful()) {
      $this->say('Files folder permissions set.');
    }
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

      $this->changeFilePerms($settings_folder, '0777');
      $this->changeFilePerms($settings_file, '0777');

      $this->taskWriteToFile($settings_file)->text("<?php\n")->run();
      if (!empty($settings)) {
        $this->recursive_print('$settings', $settings);
      }

      $this->changeFilePerms($settings_folder, '0555');
      $this->changeFilePerms($settings_file, '0555');
    }
    else {
      $this->say('No custom settings to add.');
    }
  }

  private function changeFilePerms($file, $perms = '0555', $recursive = FALSE) {
    $r = ($recursive) ? '-R' : '';
    return $this->taskExec("chmod {$r} {$perms} {$file}")->run();
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


  /**
   * Get installation task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush installation task.
   */
  protected function getInstallConfigTask() {
    return $this->taskDrushStack($this->config('bin.drush'))
      ->arg("--root={$this->root()}/web")
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
   * Get installation task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush installation task.
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
   * Run configuration import task.
   *
   * @return \Boedah\Robo\Task\Drush\DrushStack
   *   Drush configuration import task.
   */
  protected function getRunConfigImport() {
    return $this->taskDrushStack($this->config('bin.drush'))
      ->arg("--root={$this->root()}/web")
      ->drush("cim");
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
   * NPM install oe_theme.
   */
  protected function npm() {
    // $this->taskExecStack()
    //  ->stopOnFail()
    //  ->dir($this->root() . '/web/themes/contrib/oe_theme')
    //  ->exec('npm install')
    //  ->exec('npm run build')
    //  ->run();
  }

}
