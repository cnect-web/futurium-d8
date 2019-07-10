<?php

namespace Drupal\fut_activity;


/**
 * Defines the ActivityRecord class.
 */
class ActivityRecord {

  /**
   * The Activity Record ID.
   *
   * @var int
   */
  private $activity_id;

  /**
   * The tracked entity type.
   *
   * @var string
   */
  private $entity_type;

  /**
   * The tracked entity id.
   *
   * @var int
   */
  private $entity_id;

  /**
   * The activity value.
   *
   * @var int
   */
  private $activity;

  /**
   * The created timestamp.
   *
   * @var int
   */
  private $created;

  /**
   * The changed timestamp.
   *
   * @var int
   */
  private $changed;

  /**
   * Constructor.
   *
   * @param  string $entity_type
   * @param  int $entity_id
   * @param  int $activity
   * @param  int $created
   * @param  int $changed
   * @param  int $activity_id
   *
   * @return void
   */
  public function __construct($entity_type, $entity_id, $activity, $created = NULL, $changed = NULL, $activity_id = NULL) {
      $this->activity_id = $activity_id;
      $this->entity_type = $entity_type;
      $this->entity_id = $entity_id;
      $this->activity = $activity;
      $this->created = $created ?? time();
      $this->changed = $changed ?? time();
  }

  /**
   * Check if ActivityRecord is new.
   *
   * @return bool
   *   True if record is new.
   */
  public function isNew() {
    return empty($this->activity_id);
  }

  /**
   * Get record id.
   *
   * @return int
   *   ActivityRecord ID.
   */
  public function id() {
    return $this->activity_id;
  }

  /**
   * Get record entity_type.
   *
   * @return string
   *   Tracked entity type.
   */
  public function getEntityType() {
    return $this->entity_type;
  }

  /**
   * Get record entity_id.
   *
   * @return string
   *   Tracked entity id.
   */
  public function getEntityId() {
    return $this->entity_id;
  }

  /**
   * Get activity value.
   *
   * @return int
   *   Atual value of ActivityRecord.
   */
  public function getActivityValue() {
    return $this->activity;
  }

  /**
   * Set ActivityRecord activity value.
   *
   * @param int $val
   *   The new activity value.
   */
  public function setActivityValue(int $val) {
    $this->activity = $val;
  }

  /**
   * Get record created.
   *
   * @return int
   *   UNIX timestamp when ActivityRecord was created.
   */
  public function getCreated() {
    return $this->created;
  }

  /**
   * Get record changed.
   *
   * @return int
   *   UNIX timestamp when ActivityRecord was last changed.
   */
  public function getChanged() {
    return $this->changed;
  }

  /**
   * Increases Activity value by given $val.
   *
   * @param int $val
   *   The value to increase activity.
   *
   * @return ActivityRecord
   *   The ActivityRecord with increased activity value.
   */
  public function increaseActivity(int $val) {
    $this->setActivityValue($this->getActivityValue() + $val);
    return $this;
  }

  /**
   * Decrease Activity value by given $val.
   *
   * @param int $val
   *   The value to decrease activity.
   *
   * @return ActivityRecord
   *   The ActivityRecord with decreased activity value.
   */
  public function decreaseActivity(int $val) {
    $this->setActivityValue($this->getActivityValue() - $val);
    return $this;
  }

}
