<?php

namespace Drupal\fut_activity\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorBase extends PluginBase implements ActivityProcessorInterface {

  // /**
  //  * The processor settings.
  //  *
  //  * @var array
  //  */
  // protected $processorSettings;


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }





  /**
   * {@inheritdoc}
   */
  public function processActivity(BaseEntityEvent $event) {
    # code...
  }





  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }


}
