<?php

namespace Drupal\fut_activity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\hook_event_dispatcher\Event\Cron\CronEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Triggers decay event or processes plugins deppending on Event.
 *
 * @QueueWorker(
 *   id = "decay_queue",
 *   title = @Translation("Decay queue"),
 *   cron = {"time" = 10}
 * )
 */
class DecayQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

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
      case $event instanceof ActivityDecayEvent:
        // If here we get the ActivityDecayEvent we process plugins.
        $enabled_plugins = $event->getTracker()->getProcessorPlugins()->getEnabled();
        foreach ($enabled_plugins as $plugin_id => $processor_plugin) {
          $processor_plugin->processActivity($event);

          $message = $plugin_id . ' plugin processed';
          \Drupal::logger('fut_activity')->info($message);
        }

        break;

      case $event instanceof CronEvent:
        // If here we get the CronEvent we dispatch our decay event.
        $dispatcher = \Drupal::service('event_dispatcher');

        // Get all trackers.
        $trackers = $this->getTrackers();

        // Here we dispatch a Decay Event for each tracker.
        foreach ($trackers as $tracker) {
          $event = new ActivityDecayEvent($tracker);
          $dispatcher->dispatch(ActivityDecayEvent::DECAY, $event);
        }

        \Drupal::logger('fut_activity')->info("Activity Decay Dispatched");
        break;
    }
  }

  /**
   * This gets all EntityActivityTrackers config entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity "entity_activity_tracker" indexed by their ID.
   */
  protected function getTrackers() {
    return $this->entityTypeManager->getStorage('entity_activity_tracker')->loadMultiple();
  }

}
