<?php

namespace Drupal\fut_activity;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Datetime\TimeInterface;
use PDO;

/**
 * Class ActivityRecordStorage.
 */
class ActivityRecordStorage implements ActivityRecordStorageInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Constructs a new ActivityRecordStorage object.
   */
  public function __construct(Connection $database, TimeInterface $date_time) {
    $this->database = $database;
    $this->dateTime = $date_time;
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecord(int $id) {
    $query = $this->database->select('fut_activity','fa')
    ->fields('fa')
    ->condition('activity_id', $id);

    if ($result = $query->execute()->fetchAssoc()){
      return new ActivityRecord($result['entity_type'], $result['bundle'], $result['entity_id'], $result['activity'], $result['created'],  $result['changed'],  $result['activity_id']);
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecords(string $entity_type = '', string $bundle = '') {
    $query = $this->database->select('fut_activity','fa')
      ->fields('fa');
    if($entity_type) {
      $query->condition('entity_type', $entity_type);
      if($bundle) {
        $query->condition('bundle', $bundle);
      }
    }

   return $this->preparareList($query);
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecordByEntity(ContentEntityInterface $entity) {
    $query = $this->database->select('fut_activity','fa')
    ->fields('fa')
    ->condition('entity_type', $entity->getEntityTypeId())
    ->condition('bundle', $entity->bundle())
    ->condition('entity_id',$entity->id());

    if ($result = $query->execute()->fetchAssoc()){
      return new ActivityRecord($result['entity_type'], $result['bundle'], $result['entity_id'], $result['activity'], $result['created'],  $result['changed'],  $result['activity_id']);
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function createActivityRecord(ActivityRecord $activity_record) {
    if ($activity_record->isNew()) {
       $fields = [
        'entity_type' => $activity_record->getEntityType(),
        'bundle' => $activity_record->getBundle(),
        'entity_id' => $activity_record->getEntityId(),
        'activity' => $activity_record->getActivityValue(),
        'created' =>  $this->dateTime->getRequestTime(),
        'changed' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->insert('fut_activity')
          ->fields($fields)
          ->execute();
          return TRUE;
      } catch (\Throwable $th) {
        \Drupal::logger('fut_activity')->error($th->getMessage());
      }
    }
    return FALSE;
  }

  /**
   * @inheritdoc
   */
  public function updateActivityRecord(ActivityRecord $activity_record) {
    if (!$activity_record->isNew()) {
      $fields = [
        'activity' => $activity_record->getActivityValue(),
        'changed' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->update('fut_activity')
          ->fields($fields)
          ->condition('activity_id', $activity_record->id())
          ->execute();
      } catch (\Throwable $th) {
        \Drupal::logger('fut_activity')->error($th->getMessage());
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * @inheritdoc
   */
  public function deleteActivityRecord(ActivityRecord $activity_record) {
    if (!$activity_record->isNew()) {
      try {
          $this->database->delete('fut_activity')
          ->condition('activity_id', $activity_record->id())
          ->execute();
      } catch (\Throwable $th) {
        \Drupal::logger('fut_activity')->error($th->getMessage());
        return FALSE;
      }
    }
    else {
      \Drupal::logger('fut_activity')->warning('Can\'t delete activity record since there is no record for given entity.');
    }
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecordsCreated(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=') {
    $query = $this->database->select('fut_activity','fa')
      ->fields('fa');
      $query->condition('created', $timestamp, $operator);
      if($entity_type) {
        $query->condition('entity_type', $entity_type);
        if($bundle) {
          $query->condition('bundle', $bundle);
        }
      }

      return $this->preparareList($query);
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecordsChanged(int $timestamp, string $entity_type = '', string $bundle = '',  string $operator = '<=') {
    $query = $this->database->select('fut_activity','fa')
      ->fields('fa')
      ->condition('changed', $timestamp, $operator);
      if($entity_type) {
        $query->condition('entity_type', $entity_type);
        if($bundle) {
          $query->condition('bundle', $bundle);
        }
      }
    return $this->preparareList($query);
  }


  /**
   * Prepares array of ActivityRecords
   *
   * @param Drupal\Core\Database\Query\SelectInterface $query
   *
   * @return \Drupal\fut_activity\ActivityRecord[]|false
   *   A list of ActivityRecord objects or false.
   */
  protected function preparareList($query){
    if ($results = $query->execute()->fetchAllAssoc('activity_id',PDO::FETCH_ASSOC)){
      $records = [];
      foreach ($results as $activity_id => $record) {
        $records[$activity_id] = new ActivityRecord($record['entity_type'], $record['bundle'], $record['entity_id'], $record['activity'], $record['created'],  $record['changed'],  $record['activity_id']);
      }
      return $records;
    }
    return FALSE;
  }

}
