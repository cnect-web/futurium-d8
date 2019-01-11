<?php

namespace Drupal\ngf_user_list\Manager;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ngf_user_profile\MessageTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\Helper\UserHelper;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\ngf_user_profile\FlagTrait;

class UserListManager {

  use FlagTrait;
  use MessageTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * UserProfileController constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data service.
   * @param \Drupal\flag\FlagService $flag
   *   The flag service.
   */
  public function __construct(AccountInterface $current_user, MessengerInterface $messenger, UserDataInterface $userData, FlagService $flag) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->userData = $userData;
    $this->flag = $flag;

    $this->checkAccess();
  }

  protected function getCurrentUserAccount() {
    if (empty($this->currentUserAccount)) {
      $this->currentUserAccount = User::load($this->currentUser->id());
    }
    return $this->currentUserAccount;
  }

  protected function checkAccess() {
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }
  }

  public function getUserList($list_id) {
    $list_ids = \Drupal::entityQuery('ngf_user_list')
      ->condition('user_id', $this->currentUser->id())
      ->condition('list_id', $list_id)
      ->execute();

    return UserList::loadMultiple($list_ids);
  }

  /**
   * Adds user list.
   *
   * @param $name
   *   List name.
   */
  public function addUserList($name) {
    if (empty($name)) {
      $this->addError(t('Name of the list is empty.'));
    }
    else {
      $lists = \Drupal::entityQuery('ngf_user_list')
        ->condition('user_id', $this->currentUser->id())
        ->condition('name', $name)
        ->execute();

      if (empty($lists)) {
        $user_list_item = UserList::create([
          'user_id' => $this->currentUser->id(),
          'name' => $name,
        ]);
        $user_list_item->save();

        if ($user_list_item) {
          $this->addMessage(t('@name has been added', ['@name' => $name]));
        } else {
          $this->addError(t('Something went wrong, impossible to add @name list', ['@name' => $name]));
        }
      }
      else {
        $this->addError(t('You already have @name list', ['@name' => $name]));
      }
    }
  }

  public function getUserLists() {
    return UserList::loadMultiple(\Drupal::entityQuery('ngf_user_list')
      ->condition('user_id', $this->currentUser->id())
      ->execute());
  }

  public function addUserListItem($list_id, $username) {
    $list = UserList::load($list_id);
    $user = user_load_by_name($username);

    if (empty($user)) {
      $this->addError(t('User is not found.'));
    } elseif (empty($list)) {
      $this->addError(t('List is not found.'));
    } elseif ($list->getOwnerId() !== $this->currentUser->id()) {
      $this->addError(t('This list does not belong to you.'));
    } else {
      $flag = $this->getListItemFlag();
      $user_name = UserHelper::getUserFullName($user);
      if (!$flag->isFlagged($user, $this->currentUser)) {
        $this->flag->flag($flag, $list, $user);
        $this->addMessage(t('User @username has been added to the list ', ['@username' => $user_name]));
      }
      else {
        $this->addError(t('You have user @username in the list', ['@username' => $user_name]));
      }
    }
  }

  public function removeUserListItem($list_id, $username) {
    $list = UserList::load($list_id);
    $user = user_load_by_name($username);

    if (empty($user)) {
      $this->addError(t('User is not found.'));
    } elseif (empty($list)) {
      $this->addError(t('List is not found.'));
    } elseif ($list->getOwnerId() !== $this->currentUser->id()) {
      $this->addError(t('This list does not belong to you.'));
    } else {
      $flag = $this->getListItemFlag();
      $user_name = UserHelper::getUserFullName($user);
      if ($flag->isFlagged($list, $user)) {
        $this->flag->unflag($flag, $list, $user);
        $this->addMessage(t('User @username has been removed from the list ', ['@username' => $user_name]));
      }
      else {
        $this->addError(t('You do not have user @username in the list', ['@username' => $user_name]));
      }
    }
  }

  public function getUserListItems($user_list) {
    $user_list_items = [];
    if (empty($user_list)) {
      $this->addError(t('List is not found.'));
    } else {
      $flag = $this->getListItemFlag();
      $flag_user_list_items = $this->flag->getEntityFlaggings($flag, $user_list);
      $user_ids = [];
      foreach ($flag_user_list_items as $flag_user_list_item) {
        $user_ids[] = $flag_user_list_item->getOwnerId();
      }
      $user_list_items =  User::loadMultiple($user_ids);
    }

    return $user_list_items;
  }

  public function removeUserList($list_id) {
    $user_list = UserList::load($list_id);
    if (empty($user_list)) {
      $this->addError(t('List is not found.'));
    } elseif ($user_list->getOwnerId() !== $this->currentUser->id()) {
      $this->addError(t('This list does not belong to you.'));
    } else {
      // Remove list.
      $storage_handler = \Drupal::entityTypeManager()->getStorage('ngf_user_list');
      $entities = $storage_handler->loadMultiple([$list_id]);
      if (!empty($entities)) {
        $storage_handler->delete($entities);
      }

      $this->addMessage(t('List "@name" have been removed', ['@name' => $user_list->getName()]));
    }
  }

  public function removeUserListItemByList($user_list) {
    if (empty($user_list)) {
      $this->addError(t('List is not found.'));
    } else {
      $this->flag->unflagAllByEntity($user_list);
    }
  }

}
