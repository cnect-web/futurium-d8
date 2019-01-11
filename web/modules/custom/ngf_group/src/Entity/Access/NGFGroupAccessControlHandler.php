<?php

namespace Drupal\ngf_group\Entity\Access;

use Drupal\group\Entity\Access\GroupAccessControlHandler;
use Drupal\group\Access\GroupAccessResult;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the custom access control handler for the user entity type.
 */
class NGFGroupAccessControlHandler extends GroupAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if (in_array('administrator', $account->getRoles())) {
      return AccessResult::allowed();
    } else {
      switch ($operation) {
        case 'view':
          $groupVisibility = NGF_GROUP_PUBLIC;
          if ($entity->hasField('field_ngf_group_visibility')) {
            $groupVisibility = $entity->get('field_ngf_group_visibility')
              ->getString();
          }

          if (!$entity->getMember($account) && $groupVisibility == NGF_GROUP_SECRET) {
            return GroupAccessResult::forbidden();
          }
          return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'view group');

        case 'update':
          return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'edit group');

        case 'delete':
          return GroupAccessResult::allowedIfHasGroupPermission($entity, $account, 'delete group');
      }
    }

    return AccessResult::neutral();
  }

}
