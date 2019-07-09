<?php
namespace Drupal\fut_activity\Event;

use Symfony\Component\EventDispatcher\Event;


/**
 * Class BaseEntityEvent.
 */
class ActivityDecayEvent extends Event {

  const DECAY = 'event.decay';

  // /**
  //  * The list of configured trackers.
  //  *
  //  *  @var array
  //  */
  // protected $trackers = [];

  // public function __construct($trackers) {
  //   $this->trackers = $trackers;
  // }

  // public function getReferenceID() {
  //   return $this->referenceID;
  // }


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