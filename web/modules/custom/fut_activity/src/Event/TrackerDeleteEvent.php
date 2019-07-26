<?php

namespace Drupal\fut_activity\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

/**
 * Class TrackerDeleteEvent.
 *
 * @TODO: LATER ON MERGE THIS WITH TRACKER_CREATE AND DECAY IN ONE EVENT BASE CLASS!!
 */
class TrackerDeleteEvent extends Event {

  const TRACKER_DELETE = 'event.tracker.delete';

  /**
   * The EntityActivityTracker.
   *
   * @var \Drupal\fut_activity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * TrackerDeleteEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityActivityTrackerInterface $tracker
   *   The EntityActivityTracker.
   */
  public function __construct(EntityActivityTrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * Get the Tracker.
   *
   * @return \Drupal\fut_activity\EntityActivityTrackerInterface
   *   The Tracker.
   */
  public function getTracker() {
    return $this->tracker;
  }

  /**
   * Get the dispatcher type.
   *
   * @return string
   *   The dispatcher type.
   */
  public function getDispatcherType() {
    return self::TRACKER_DELETE;
  }

}
