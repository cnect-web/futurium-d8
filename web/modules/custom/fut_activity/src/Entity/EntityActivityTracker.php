<?php

namespace Drupal\fut_activity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\fut_activity\Plugin\ActivityProcessorCollection;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

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
class EntityActivityTracker extends ConfigEntityBase implements EntityActivityTrackerInterface, EntityWithPluginCollectionInterface {

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

  // /**
  //  * Processors instances IDs.
  //  *
  //  * @var array
  //  */
  // protected $activity_processors;

  /**
   * The Activity Tracker processor plugins configuration keyed by their id.
   *
   * @var array
   */
  public $activity_processors = [];

  /**
   * Holds the collection of processor plugins that are attached to this
   * Entity Activity Tracker.
   *
   * @var \Drupal\fut_activity\Plugin\ActivityProcessorCollection
   */
  protected $processorCollection;


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
  public function getProcessorPlugins() {
    if (!isset($this->processorCollection)) {
      $this->processorCollection = new ActivityProcessorCollection(\Drupal::service('fut_activity.plugin.manager.activity_processor'), $this->activity_processors);
    }
    return $this->processorCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorPlugin($instance_id) {
    return $this->getProcessorPlugins()->get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEnabledProcessorsPlugins() {
    return $this->getProcessorPlugins()->getEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['activity_processors' => $this->getProcessorPlugins()];
  }


  // /**
  //  * {@inheritdoc}
  //  */
  // public function getProcessors() {
  //   return $this->activity_processors;
  // }

  //  /**
  //  * {@inheritdoc}
  //  */
  // public function getProcessor($key) {
  //   if (!isset($this->activity_processors[$key])) {
  //     return NULL;
  //   }
  //   return $this->activity_processors[$key];
  // }


}
