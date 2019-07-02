<?php

namespace Drupal\fut_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Entity activity tracker entities.
 */
interface EntityActivityTrackerInterface extends ConfigEntityInterface {

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
   * Gets the decay value.
   *
   * @return int
   *   The activity value to subtract when preform a decay.
   */
  public function getDecay();

  /**
   * Gets the decay granularity.
   *
   * @return int
   *   The time in seconds that the activity value is kept before applying the decay.
   */
  public function getDecayGranularity();

  /**
   * Gets the halflife value.
   *
   * @return int
   *   The time in seconds in which the activity value halves.
   */
  public function getHalflife();

  /**
   * Gets the timestap of entity creation.
   *
   * @return int
   *   The timestamp of creation.
   */
  public function getActivityCreation();

  /**
   * Gets the timestap of last update.
   *
   * @return int
   *   The timestamp of last update.
   */
  public function getActivityUpdate();
}
