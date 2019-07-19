<?php

namespace Drupal\fut_activity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface ActivityRecordStorageInterface.
 */
interface ActivityRecordStorageInterface {

  /**
   * Gets a ActivityRecord given a certain id.
   *
   * @param  int $id
   *
   * @return \Drupal\fut_activity\ActivityRecord
   *   The ActivityRecord object.
   */
  public function getActivityRecord(int $id);

  /**
   * Gets a list of ActivityRecords.
   *
   * @param string $entity_type
   *   Optional get list of given entity_type.
   *
   * @param string $bundle
   *   Optional get list of given entity_type and bundle.
   *
   * @return \Drupal\fut_activity\ActivityRecord[]
   *   A list of ActivityRecord objects.
   */
  public function getActivityRecords(string $entity_type = '', string $bundle = '');

  /**
   * Gets a ActivityRecord given a Entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Content entity that is being tracked.
   *
   * @return \Drupal\fut_activity\ActivityRecord|false
   *   The ActivityRecord object or FALSE.
   */
  public function getActivityRecordByEntity(ContentEntityInterface $entity);

  /**
   * Creates an ActivityRecord on database
   *
   * @param \Drupal\fut_activity\ActivityRecord $activity_record
   *   ActivityRecord object that should be created.
   *
   * @return bool
   *   TRUE if sucessfull.
   */
  public function createActivityRecord(ActivityRecord $activity_record);

  /**
   * Updates an ActivityRecord on database
   *
   * @param \Drupal\fut_activity\ActivityRecord $activity_record
   *   ActivityRecord object that should be updated with updated values.
   *
   * @return bool
   *   TRUE if sucessfull.
   */
  public function updateActivityRecord(ActivityRecord $activity_record);

  /**
   * Deletes an ActivityRecord on database
   *
   * @param \Drupal\fut_activity\ActivityRecord $activity_record
   *   ActivityRecord object that should be deleted.
   *
   * @return bool
   *   TRUE if sucessfull.
   */
  public function deleteActivityRecord(ActivityRecord $activity_record);

  /**
   * Gets a list of ActivityRecords filtering by created timestamp.
   *
   * This method will get activity records by compering record creation date
   * by default the operator parameter is "less than or equal to" (<=)
   * this means that we get all records were created before given timestamp.
   *
   * @param  int $timestamp
   *   UNIX timestamp to use as filter.
   * @param  string $entity_type
   *   (Optional) Defines entity_type of wich records we should get.
   * @param  string $bundle
   *   (Optional) Defines bundle of wich records we should get.
   * @param  string $operator
   *   (Optional) Defines if we want a record created before or after given timestamp.
   *
   * @return void
   */
  public function getActivityRecordsCreated(int $timestamp, string $entity_type = '', string $bundle = '', string $operator = '<=');

  /**
   * Gets a list of ActivityRecords filtering by changed timestamp.
   *
   * This method will get activity records by compering record creation date
   * by default the operator parameter is "less than or equal to" (<=)
   * this means that we get all records were changed before given timestamp.
   *
   * @param  int $timestamp
   *   UNIX timestamp to use as filter.
   * @param  string $entity_type
   *   (Optional) Defines entity_type of wich records we should get.
   * @param  string $bundle
   *   (Optional) Defines bundle of wich records we should get.
   * @param  string $operator
   *   (Optional) Defines if we want a record changed before or after given timestamp.
   *
   * @return void
   */
  public function getActivityRecordsChanged(int $timestamp, string $entity_type = '', string $bundle = '',  string $operator = '<=');

}
