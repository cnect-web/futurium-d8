<?php

namespace Drupal\ngf_user_profile\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Represents a configurable user full name field.
 */
class FullNameFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();
    if (empty($entity->get('field_ngf_first_name')->value) && empty($entity->get('field_ngf_last_name')->value)) {
      $value = $entity->getUserName();
    }
    else {
      $value = "{$entity->get('field_ngf_first_name')->value} {$entity->get('field_ngf_last_name')->value}";
    }

    $this->list[0] = $this->createItem(0, $value);
  }

}
