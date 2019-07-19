<?php

namespace Drupal\fut_activity\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines an interface for Activity processor plugins.
 */
interface ActivityProcessorInterface extends PluginInspectionInterface, ConfigurableInterface, PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * ProcessActivity.
   */
  public function processActivity(Event $event);

}
