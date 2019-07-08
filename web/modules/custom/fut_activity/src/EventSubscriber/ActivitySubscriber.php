<?php

namespace Drupal\fut_activity\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\fut_activity\ActivityProcessorInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class FutActivitySubscriber.
 */
class ActivitySubscriber implements EventSubscriberInterface {


  /**
   * The activity processor.
   *
   * @var \Drupal\fut_activity\ActivityProcessorInterface
   */
  protected $activityProcessor;


  /**
   * Constructs a new FutActivitySubscriber object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'entityInsert',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'entityUpdate',
      HookEventDispatcherInterface::ENTITY_DELETE => 'entityDelete',
    ];
  }

  /**
   * Entity insert.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityInsertEvent $event
   *   The event.
   */
  public function entityInsert(EntityInsertEvent $event) {
    // Do some fancy stuff with new entity.

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $event->getEntity();

    // HERE WE NEEED TO DO SOMETHING ONLY FOR OUR CONTENT ENTITIES
    if ($entity->getEntityTypeId() == 'node') {
      // Get entity activity tracker config for this entity.

      $tracker = $this->getTrackerConfig($entity);


      $enabled_plugins = $tracker->getProcessorPlugins()->getEnabled();

      foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
        $processor_plugin->processActivity($event);
      }


      // $this->activityProcessor->incrementActivityValue($tracker_config, $entity, 'create');

      // Add loger here.
    }

  }

  /**
   * Entity update.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   The event.
   */
  public function entityUpdate(EntityUpdateEvent $event) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $event->getEntity();

    // dpm($entity->label());

    // HERE WE NEEED TO DO SOMETHING ONLY FOR OUR CONTENT ENTITIES
    if ($entity->getEntityTypeId() == 'node') {
      // Get entity activity tracker config for this entity.

      /** @var \Drupal\fut_activity\Entity\EntityActivityTracker $tracker  */
      $tracker = $this->getTrackerConfig($entity);


      $enabled_plugins = $tracker->getProcessorPlugins()->getEnabled();

      foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
        $processor_plugin->processActivity($event);
      }



      $teste="aa";
      // $this->activityProcessor->incrementActivityValue($tracker_config, $entity, 'update');


      //
    }

  }

  /**
   * Entity delete.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityDeleteEvent $event
   *   The event.
   */
  public function entityDelete(EntityDeleteEvent $event) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $event->getEntity();

    // HERE WE NEEED TO DO SOMETHING ONLY FOR OUR CONTENT ENTITIES
    // if ($entity->getEntityTypeId() == 'node') {
    //   $this->activityProcessor->deleteActivityRecord($entity);
    // }

  }



  //this will move from here

  /**
   * getTrackerConfig
   *
   * @param  mixed $entity
   *
   */
  public function getTrackerConfig(ContentEntityInterface $entity) {
    $entity_bundle = $entity->bundle();
    $config_id = $this->entityTypeManager->getStorage('entity_activity_tracker')->getQuery()
      ->condition('entity_bundle',$entity_bundle)
      ->execute();

    $config_id = reset($config_id);

    $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->load($config_id);

    // $traker_config = $this->configFactory->get('fut_activity.entity_activity_tracker.'.$config_id);

    return $tracker;

  }



}
