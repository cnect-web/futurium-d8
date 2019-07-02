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
  public function __construct(ActivityProcessorInterface $activity_processor) {
    $this->activityProcessor = $activity_processor;
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
    $entity = $event->getEntity();

    // HERE WE NEEED TO DO SOMETHING ONLY FOR OUR CONTENT ENTITIES
    if ($entity->getEntityTypeId() == 'node') {
      // Get entity activity tracker config for this entity.

      /** @var \Drupal\Core\Config\ImmutableConfig $tracker_config  */
      $tracker_config = $this->activityProcessor->getTrackerConfig($entity);
      $this->activityProcessor->incrementActivityValue($tracker_config, $entity, 'create');

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

      /** @var \Drupal\Core\Config\ImmutableConfig $tracker_config  */
      $tracker_config = $this->activityProcessor->getTrackerConfig($entity);
      $this->activityProcessor->incrementActivityValue($tracker_config, $entity, 'update');


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
    if ($entity->getEntityTypeId() == 'node') {
      $this->activityProcessor->deleteActivityRecord($entity);
    }

  }



}
