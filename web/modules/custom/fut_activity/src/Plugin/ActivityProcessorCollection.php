<?php

namespace Drupal\fut_activity\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of activity processors plugins.
 */
class ActivityProcessorCollection extends DefaultLazyPluginCollection {

  /**
   * All processor plugin definitions.
   *
   * @var array
   */
  protected $definitions;

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\fut_activity\Plugin\ActivityProcessorInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Retrieves all enabled plugins.
   */
  public function getEnabled() {
    $this->getAll();
    $enabled = [];
    foreach ($this->getConfiguration() as $key => $value) {
      if (isset($value['enabled']) && $value['enabled'] == TRUE) {
        $enabled[$key] = $this->get($key);
      }
    }
    return $enabled;
  }

  /**
   * Retrieves all plugins definitions and creates an instance for each
   * one.
   */
  public function getAll() {
    // Retrieve all available behavior plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();
    }
    // Ensure that there is an instance of all available plugins.
    // $instance_id is the $plugin_id for processor plugins, since a processor plugin can only
    // exist once in a paragraphs type.
    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $configuration = isset($this->configurations[$instance_id]) ? $this->configurations[$instance_id] : [];
    $this->set($instance_id, $this->manager->createInstance($instance_id, $configuration));
  }

}
