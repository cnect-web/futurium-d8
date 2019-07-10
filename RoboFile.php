<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
use fut\Robo\Tasks as futTasks;
/**
 * Class RoboFile.
 */
class RoboFile extends futTasks {

  private $defaultOp = 'cs,unit';
  private $defaultPaths = 'web/modules/custom,web/themes/contrib/blellow';

  /**
   * Build project.
   *
   * @command project:build
   * @aliases pb
   */
  public function build($branch) {
    // Change Branch.
    $this
      ->taskGitStack()
      ->stopOnFail()
      ->checkout($branch)
      ->pull()
      ->run();

    // Checkout any local changes on settings.php
    if ($this->taskExec('git checkout -- web/sites/default/settings.php')->run()->wasSuccessful()) {
      $this->say('Cleared up settings.php changes.');
    }

    // Install website.
    $this->projectInstallConfig();
  }

  /**
   * Update project dependencies.
   *
   * @command project:update-dep
   * @aliases pud
   */
  public function updateSiteDependencies() {
    // Run Composer update.
    $this
      ->taskComposerUpdate()
      ->run();
  }

  /**
   * Import config from filesystem to database.
   *
   * @command project:import-config
   * @aliases imc
   */
  public function importConfig() {
    $this->taskDrushStack($this->config('bin.drush'))
      ->arg('-r', 'web/')
      ->exec('cache-clear drush')
      ->exec('updb')
      ->exec('cim -y')
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
    $this->taskDrushStack($this->config('bin.drush'))
      ->arg('-r', 'web/')
      ->exec('cache-clear drush')
      ->exec('cex -y')
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
   * Run QA tasks.
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

}
