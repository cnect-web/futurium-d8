<?php

namespace Drupal\fut_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity activity tracker entities.
 */
interface EntityActivityTrackerInterface extends ConfigEntityInterface {

  const ALLOWED_ENTITY_TYPES = ['node', 'user', 'taxonomy_term',  ];

  /**
   * Gets the entity type to wich this config applies.
   *
   * @return string
   *   The Traget Entity type.
   */
  public function getTargetEntityType();

  /**
   * Gets the entity type bundle to wich this config applies.
   *
   * @return string
   *   The Bundle.
   */
  public function getTargetEntityBundle();


  /**
   * getProcessorPlugins
   *
   * @return void
   */
  public function getProcessorPlugins();

  /**
   * getProcessorPlugin
   *
   * @param  mixed $instance_id
   *
   * @return void
   */
  public function getProcessorPlugin($instance_id);

  /**
   * getEnabledProcessorsPlugins
   *
   * @return void
   */
  public function getEnabledProcessorsPlugins();


  // /**
  //  * Gets the list of activity processors.
  //  *
  //  * @return array
  //  *   The list of processors.
  //  */
  // public function getProcessors();

  // /**
  //  * Get a specific processor.
  //  *
  //  * @return \Drupal\fut_activity\Plugin\ActivityProcessorInterface
  //  *   A specific ActivityProcessor.
  //  */
  // public function getProcessor($key);

}
