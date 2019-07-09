<?php

namespace Drupal\fut_activity;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Component\Datetime\TimeInterface;

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
   * getActivityRecordIdByEntity
   *
   * @param  \Drupal\Core\Entity\ContentEntityInterface  $entity
   *  The entity the is being tracked.
   *
   * @return mixed Record ID or FALSE
   */
  protected function getActivityRecordIdByEntity(ContentEntityInterface $entity) {
    $query = $this->database->select('fut_activity','fa')
    ->fields('fa',['activity_id'])
    ->condition('entity_type', $entity->getEntityTypeId())
    ->condition('entity_id',$entity->id());

    return $query->execute()->fetchField();
  }

  /**
   * @inheritdoc
   */
  public function updateActivityRecord(ContentEntityInterface $entity, $value = 0, $op = '+') {
    $activity_record_id = $this->getActivityRecordIdByEntity($entity);
    if ($activity_record_id) {
      try {
        $query = $this->database->update('fut_activity')
          ->fields(['changed'=> $this->dateTime->getRequestTime()])
          ->condition('activity_id', $activity_record_id)
          ->condition('entity_type',$entity->getEntityTypeId());
          switch ($op) {
            case '-':
              $query->expression('activity', 'activity - :value', [':value' => $value])
                ->execute();
              break;
            default:
              $query->expression('activity', 'activity + :value', [':value' => $value])
                ->execute();
              break;
          }
      } catch (\Throwable $th) {
        \Drupal::logger('system')->error($th->getMessage());
      }
    }
    else{
      // Create record.
      $fields = [
        'entity_type' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'activity' => $value,
        'created' =>  $this->dateTime->getRequestTime(),
        'changed' => $this->dateTime->getRequestTime(),
      ];
      try {
        $this->database->insert('fut_activity')
          ->fields($fields)
          ->execute();
      } catch (\Throwable $th) {
        \Drupal::logger('system')->error($th->getMessage());
      }
    }
  }

  /**
   * @inheritdoc
   */
  public function deleteActivityRecord(ContentEntityInterface $entity) {
    $activity_record_id = $this->getActivityRecordIdByEntity($entity);
    if ($activity_record_id) {
      try {
          $this->database->delete('fut_activity')
          ->condition('activity_id', $activity_record_id)
          ->condition('entity_id', $entity->id(), 'IN')
          ->execute();

      } catch (\Throwable $th) {
        // Todo: create my chanel / logger.
        \Drupal::logger('system')->error($th->getMessage());
      }
    }
    else {
      \Drupal::logger('system')->warning('Can\'t delete activity record since there is no record for given entity.');
    }
  }

  /**
   * @inheritdoc
   */
  public function getActivityRecords(string $entity_type = '') {
    $query = $this->database->select('fut_activity','fa')
      ->fields('fa');
    if($entity_type){
      $query->condition('entity_type', $entity_type);
    }
    return $query->execute()->fetchAssoc();
  }

}
