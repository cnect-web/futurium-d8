<?php
namespace Drupal\fut_activity\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ActivityDecayEvent.
 */
class ActivityDecayEvent extends Event {

  const DECAY = 'event.decay';

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