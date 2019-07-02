<?php

namespace Drupal\fut_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Entity activity tracker entity.
 *
 * @ConfigEntityType(
 *   id = "entity_activity_tracker",
 *   label = @Translation("Entity activity tracker"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fut_activity\EntityActivityTrackerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\fut_activity\Form\EntityActivityTrackerForm",
 *       "edit" = "Drupal\fut_activity\Form\EntityActivityTrackerForm",
 *       "delete" = "Drupal\fut_activity\Form\EntityActivityTrackerDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\fut_activity\EntityActivityTrackerHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "entity_activity_tracker",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/entity_activity_tracker/{entity_activity_tracker}",
 *     "add-form" = "/admin/config/entity_activity_tracker/add",
 *     "edit-form" = "/admin/config/entity_activity_tracker/{entity_activity_tracker}/edit",
 *     "delete-form" = "/admin/config/entity_activity_tracker/{entity_activity_tracker}/delete",
 *     "collection" = "/admin/config/entity_activity_tracker"
 *   }
 * )
 */
class EntityActivityTracker extends ConfigEntityBase implements EntityActivityTrackerInterface {

  /**
   * The Entity activity tracker ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Entity activity tracker label.
   *
   * @var string
   */
  protected $label;


  /**
   * The Entity type where this config will be used.
   *
   * @var string
   */
  protected $entity_type;

  /**
   * The bundle where this config will be used.
   *
   * @var string
   */
  protected $entity_bundle;

  /**
   * The activity value to subtract when preform a decay.
   *
   * @var int
   */
  protected $decay;

  /**
   * The time in seconds that the activity value is kept before applying the decay.
   *
   * @var int
   */
  protected $decay_granularity;

  /**
   * The time in seconds in which the activity value halves.
   *
   * @var int
   */
  protected $halflife;

  /**
   * The activity value on entity creation.
   *
   * @var int
   */
  protected $activity_creation;

  /**
   * The activity value to increment on entity update.
   *
   * @var int
   */
  protected $activity_update;


  // TODO: IDK how to handle the comment part :S
  // /**
  //  * The activity value to increment when a comment is added.
  //  *
  //  * @var int
  //  */
  // protected $activity_comment;


  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType() {
    return $this->entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityBundle() {
    return $this->entity_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecay() {
    return $this->decay;
  }

  /**
   * {@inheritdoc}
   */
  public function getDecayGranularity() {
    return $this->decay_granularity;
  }

  /**
   * {@inheritdoc}
   */
  public function getHalflife() {
    return $this->halflife;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityCreation() {
    return $this->activity_creation;
  }

  /**
   * {@inheritdoc}
   */
  public function getActivityUpdate() {
    return $this->activity_update;
  }
}
