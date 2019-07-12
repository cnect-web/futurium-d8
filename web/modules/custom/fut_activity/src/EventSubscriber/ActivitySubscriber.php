<?php

namespace Drupal\fut_activity\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\hook_event_dispatcher\Event\Cron\CronEvent;
use Drupal\Core\Queue\QueueFactory;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, QueueFactory $queue) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->queue = $queue;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_INSERT => 'entityOperations',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'entityOperations',
      HookEventDispatcherInterface::ENTITY_DELETE => 'entityOperations',
      HookEventDispatcherInterface::CRON => 'scheduleDecay',
      ActivityDecayEvent::DECAY => 'applyDecay',
    ];
  }

  /**
   * This sends the event to ActivityProcessorQueue.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent $event
   *   The event.
   */
  public function entityOperations(BaseEntityEvent $event) {
    if (in_array($event->getEntity()->getEntityTypeId(),EntityActivityTrackerInterface::ALLOWED_ENTITY_TYPES)) {
      $processors_queue = $this->queue->get('activity_processor_queue');
      $processors_queue->createItem($event);
    }
  }


  /**
   * This creates a item in Decay queue to later be processed.
   *
   * @param  \Drupal\fut_activity\Event\ActivityDecayEvent $event
   *   The decay event.
   *
   */
  public function applyDecay(ActivityDecayEvent $event) {
    $decay_queue = $this->queue->get('decay_queue');
    $decay_queue->createItem($event);

  }


  /**
   * This creates a item in Decay queue to dispatch ActivityDecayEvent.
   *
   * @param  mixed $event
   *  The cron event.
   *
   * @return void
   */
  public function scheduleDecay(CronEvent $event) {
    /** @var  \Drupal\Core\Queue\QueueInterface $decay_queue */
    $decay_queue = $this->queue->get('decay_queue');
    $decay_queue->createItem($event);
  }

}
