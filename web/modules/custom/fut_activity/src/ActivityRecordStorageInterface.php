<?php

namespace Drupal\fut_activity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface ActivityRecordStorageInterface.
 */
interface ActivityRecordStorageInterface {

  /**
   * updateActivityRecord
   *
   * @param  \Drupal\Core\Entity\ContentEntityInterface  $entity
   * @param  mixed $value
   * @param  mixed $op
   *
   * @return void
   */
  public function updateActivityRecord(ContentEntityInterface $entity, $value = 0, $op = '+');

  /**
   * deleteActivityRecord
   *
   * @param  \Drupal\Core\Entity\ContentEntityInterface  $entity
   *
   * @return void
   */
  public function deleteActivityRecord(ContentEntityInterface $entity);

}
