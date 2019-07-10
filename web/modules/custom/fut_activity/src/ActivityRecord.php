<?php

namespace Drupal\fut_activity;


class ActivityRecord {


    private $activity_id;

    private $entity_type;

    private $entity_id;

    private $activity;

    private $created;

    private $changed;

    public function __construct($entity_type, $entity_id, $activity, $created = NULL, $changed = NULL, $activity_id = NULL) {
        $this->activity_id = $activity_id;
        $this->entity_type = $entity_type;
        $this->entity_id = $entity_id;
        $this->activity = $activity;
        $this->created = $created ?? time();
        $this->changed = $changed ?? time();
    }

    public function isNew() {
      return empty($this->activity_id);
    }

    public function id() {
      return $this->activity_id;
    }

    public function getEntityType() {
      return $this->entity_type;
    }

    public function getEntityId() {
      return $this->entity_id;
    }

    public function getActivityValue() {
      return $this->activity;
    }

    public function setActivityValue(int $val) {
      $this->activity = $val;
    }

    public function getCreated() {
      return $this->created;
    }

    public function getChanged() {
      return $this->changed;
    }

    public function increaseActivity(int $val) {
      $this->setActivityValue($this->getActivityValue() + $val);
      return $this;
    }

    public function decreaseActivity(int $val) {
      $this->setActivityValue($this->getActivityValue() - $val);
      return $this;
    }

}
