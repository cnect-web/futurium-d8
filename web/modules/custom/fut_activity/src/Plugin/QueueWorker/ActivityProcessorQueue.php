<?php

namespace Drupal\fut_activity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_activity\Event\TrackerCreateEvent;
use Drupal\fut_activity\Event\TrackerDeleteEvent;

/**
 * Processes ActivityProcessor plugins.
 *
 * @QueueWorker(
 *   id = "activity_processor_queue",
 *   title = @Translation("Activity Processor queue"),
 *   cron = {"time" = 10}
 * )
 */
class ActivityProcessorQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ActivityProcessorQueue.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($event) {

    switch ($event) {
      case $event instanceof BaseEntityEvent:
        foreach ($this->getTrackerFromEvent($event) as $tracker) {
          $enabled_plugins = $tracker->getProcessorPlugins()->getEnabled();
          foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
            $processor_plugin->processActivity($event);
            $message = $plugin_id . ' plugin processed';
            \Drupal::logger('fut_activity')->info($message);
          }
        }
        break;

      case $event instanceof TrackerCreateEvent:
      case $event instanceof TrackerDeleteEvent:
        $enabled_plugins = $event->getTracker()->getProcessorPlugins()->getEnabled();
        foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
          $processor_plugin->processActivity($event);
          $message = $plugin_id . ' plugin processed';
          \Drupal::logger('fut_activity')->info($message);
        }
        break;
    }
    \Drupal::logger('fut_activity')->info("Processing item of ActivityProcessorQueue");
  }

  /**
   * Get Tracker from given Event.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent $event
   *   An entity event.
   *
   * @return \Drupal\fut_activity\Entity\EntityActivityTrackerInterface
   *   The tracker config entity.
   */
  protected function getTrackerFromEvent(BaseEntityEvent $event) {
    $properties = [
      'entity_type' => $event->getEntity()->getEntityTypeId(),
      'entity_bundle' => $event->getEntity()->bundle(),
    ];

    $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->loadByProperties($properties);

    return $tracker;
  }

}
