<?php

namespace Drupal\fut_activity\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\fut_activity\Event\ActivityDecayEvent;

/**
 * Triggers decay event.
 *
 * @QueueWorker(
 *   id = "decay_queue",
 *   title = @Translation("Decay queue"),
 *   cron = {"time" = 1}
 * )
 */
class DecayQueue extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // in data i should have the trackers and the decay plugins.
    $dispatcher = \Drupal::service('event_dispatcher');
    $event = new ActivityDecayEvent();
    $dispatcher->dispatch(ActivityDecayEvent::DECAY, $event);

    \Drupal::logger('fut_activity')->info("Activity Decay Dispatched");
  }
}