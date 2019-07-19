<?php
namespace Drupal\fut_activity\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

/**
 * Class TrackerCreateEvent.
 */
class TrackerCreateEvent extends Event {

  const TRACKER_CREATE = 'event.tracker.create';

  /**
   * The EntityActivityTracker.
   *
   * @var \Drupal\fut_activity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * TrackerCreateEvent constructor.
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
    return self::TRACKER_CREATE;
  }

}