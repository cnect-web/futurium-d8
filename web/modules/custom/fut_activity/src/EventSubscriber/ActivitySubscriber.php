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
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\hook_event_dispatcher\Event\Cron\CronEvent;

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
      HookEventDispatcherInterface::CRON => 'scheduleDecay',
      ActivityDecayEvent::DECAY => 'applyDecay',
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

      $tracker = $this->getTracker($entity);


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
      $tracker = $this->getTracker($entity);


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

    if ($entity->getEntityTypeId() == 'node') {


      /** @var \Drupal\fut_activity\Entity\EntityActivityTracker $tracker  */
      $tracker = $this->getTracker($entity);

      $enabled_plugins = $tracker->getProcessorPlugins()->getEnabled();
      foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
        $processor_plugin->processActivity($event);
      }
    }

    // HERE WE NEEED TO DO SOMETHING ONLY FOR OUR CONTENT ENTITIES
    // if ($entity->getEntityTypeId() == 'node') {
    //   $this->activityProcessor->deleteActivityRecord($entity);
    // }

  }



  /**
   * applyDecay on DECAY EVENT
   *
   * @param  \Drupal\fut_activity\Event\ActivityDecayEvent $event
   *
   */
  public function applyDecay(ActivityDecayEvent $event) {
    // Here we must run the processActivity of decay plugins.
    // I need a way to get just the decay plugins -> see plugin collection
    // (later we do this now run every plugin and each plugin is responsible to run if the event is appropriate)

    $trackers = $this->getTrackers();

    foreach ($trackers as $tracker_id => $tracker) {
      $enabled_plugins = $tracker->getProcessorPlugins()->getEnabled();
      foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
        $processor_plugin->processActivity($event);
        $message = $plugin_id . ' plugin processed';
        \Drupal::logger('fut_activity')->info($message);
      }
    }

  }


  /**
   * scheduleDecay creates DecayQueue
   *
   * @param  mixed $event
   *
   * @return void
   */
  public function scheduleDecay(CronEvent $event) {
    $queue = \Drupal::queue('decay_queue');
    $queue->createItem($this->getTrackers());
  }

  //this will move from here

  /**
   * getTracker
   *
   * @param  mixed $entity
   *
   */
  public function getTracker(ContentEntityInterface $entity) {
    $entity_bundle = $entity->bundle();
    $config_id = $this->entityTypeManager->getStorage('entity_activity_tracker')->getQuery()
      ->condition('entity_bundle',$entity_bundle)
      ->execute();

    $config_id = reset($config_id);

    $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->load($config_id);

    return $tracker;
  }


  /**
   * getTrackers
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *    An array of entity "entity_activity_tracker" indexed by their ID.
   */
  public function getTrackers() {
    return $this->entityTypeManager->getStorage('entity_activity_tracker')->loadMultiple();
  }



}
