<?php

namespace Drupal\ngf_user_list\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User list entity.
 *
 * @see \Drupal\ngf_user_list\Entity\UserList.
 */
class UserListAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ngf_user_list\Entity\UserListInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view ngf user list entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit ngf user list entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete ngf user list entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add ngf user list entities');
  }

}
