<?php
namespace Drupal\fut_activity\Event;

use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

/**
 * Class ActivityDecayEvent.
 */
class ActivityDecayEvent extends Event {

  const DECAY = 'event.decay';

  /**
   * The EntityActivityTracker.
   *
   * @var \Drupal\fut_activity\EntityActivityTrackerInterface
   */
  protected $tracker;

  /**
   * BaseEntityEvent constructor.
   *
   * @param \Drupal\Core\Entity\EntityActivityTrackerInterface $tracker
   *   The EntityActivityTracker.
   */
  public function __construct(EntityActivityTrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * Get the Entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The Entity.
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
    return self::DECAY;
  }

}