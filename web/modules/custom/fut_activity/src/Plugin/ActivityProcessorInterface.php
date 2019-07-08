<?php

namespace Drupal\fut_activity\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;

/**
 * Defines an interface for Activity processor plugins.
 */
interface ActivityProcessorInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface {


  /**
   * processActivity
   *
   */
  public function processActivity(BaseEntityEvent $event);
}
