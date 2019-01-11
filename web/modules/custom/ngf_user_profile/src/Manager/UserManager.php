<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\ngf_user_profile\MessageTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\Helper\UserHelper;
use Drupal\ngf_user_profile\Entity\UserList;
use Drupal\ngf_user_profile\FlagTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserManager {

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
      $this->addMessage(t('You need to be registered to see other people profiles'));
      $response = new RedirectResponse(Url::fromRoute('ngf_user_registration')->toString());
      $response->send();
    }
  }

  public function getUserList($list_id) {
    $list_ids = \Drupal::entityQuery('ngf_user_list')
      ->condition('user_id', $this->currentUser->id())
      ->condition('list_id', $list_id)
      ->execute();

    return UserList::loadMultiple($list_ids);
  }

  public function getCountFollowingUsersList($user) {
    return count($this->flag->getEntityFlaggings($this->getFollowUserFlag(), $user));
  }

  public function getCountFollowersUsersList($user) {
    return count($this->getUserFlaggedItemsByFlagId('ngf_follow_user', $user->id()));
  }

  public function getCountSavedContent($user) {
    return count($this->getUserFlaggedItemsByFlagId('ngf_save_content', $user->id()));
  }

  /**
   * Follow user.
   *
   * @param string $username
   *   Username.
   */
  public function follow($username) {
    $user = user_load_by_name($username);
    if (empty($user)) {
      $this->addError(t('User is not found.'));
    }
    else {
      $user_name = UserHelper::getUserFullName($user);
      $flag = $this->getFollowUserFlag();
      if (!$flag->isFlagged($user, $this->currentUser)) {
        $this->flag->flag($flag, $user);

        $this->addMessage(t('You are following @username',
          ['@username' => $user_name]));
      } else {
        $this->addError(t('You are already following @username',
          ['@username' => $user_name]));
      }
    }
  }

  /**
   * Remove passed user to the list of followed users.
   *
   * @param string $username
   *   Username.
   */
  public function unfollow($username) {
    $user = user_load_by_name($username);

    if (empty($user)) {
      $this->addError(t('User is not found.'));
    }
    else {
      $user_name = UserHelper::getUserFullName($user);
      $flag = $this->getFollowUserFlag();
      if ($flag->isFlagged($user, $this->currentUser)) {
        $this->flag->unflag($flag, $user);

        $this->addMessage(t('You are not following anymore @username',
          ['@username' => $user_name]));
      }
      else {
        $this->addError(t('You are not following @username',
          ['@username' => $user_name]));
      }
    }
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
    $list = UserList::load($user_list->id());
    $user_list_items = [];
    if (empty($list)) {
      $this->addError(t('List is not found.'));
    } else {
      $flag = $this->getListItemFlag();
      $flag_user_list_items = $this->flag->getEntityFlaggings($flag, $list);
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
      // Remove related list items.
      $this->removeUserListItemByList($user_list);

      // Remove list.
      $storage_handler = \Drupal::entityTypeManager()->getStorage('ngf_user_list');
      $entities = $storage_handler->loadMultiple([$list_id]);
      if (!empty($entities)) {
        $storage_handler->delete($entities);
      }

      $this->addMessage(t('List "@name" have been removed', ['@name' => $user_list->getName()]));
    }
  }

  protected function removeUserListItemByList($user_list) {
    if (empty($user_list)) {
      $this->addError(t('List is not found.'));
    } else {
      $this->flag->unflagAllByEntity($user_list);
    }
  }

  public function getFollowers($user) {
    return $this->flag->getFlaggingUsers($user, $this->getFollowUserFlag());
  }

  public function getFollowingUsersList($user) {
    if (empty($user)) {
      $user = $this->getCurrentUserAccount();
    }
    $followed_user_items = $this->getUserFlaggedItemsByFlagId('ngf_follow_user', $user->id());
    $user_ids = [];
    foreach ($followed_user_items as $user_item) {
      $user_ids[] = $user_item->entity_id;
    }
    return User::loadMultiple($user_ids);
  }

}
