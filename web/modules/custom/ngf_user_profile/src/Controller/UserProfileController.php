<?php

namespace Drupal\ngf_user_profile\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ngf_user_profile\Helper\UserHelper;
use Drupal\ngf_user_profile\Manager\UserListManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserProfileController.
 */
class UserProfileController extends UserProfileControllerBase {

  /**
   * The user list item manager service.
   *
   * @var Drupal\ngf_user_profile\Manager\userManager
   */
  protected $userManager;

  /**
   * UserProfileController constructor.
   *
   * @param Drupal\ngf_user_profile\Manager\UserManager $user_manager
   *   The user manager.
   */
  public function __construct(UserListManager $user_manager) {
    $this->userManager = $user_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngf_user_profile.user_manager')
    );
  }

  /**
   * Follow a user
   *
   * @param string $username
   *   User name
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirection response
   */
  public function follow($username) {
    $this->userManager->follow($username);
    // Redirect back to a user profile.
    return $this->redirect('ngf_user_profile.profile', ['username' => $username]);
  }

  /**
   * Unfollow a user
   *
   * @param string $username
   *   User name
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirection response
   */
  public function unfollow($username) {
    $this->userManager->unfollow($username);
    // Redirect back to a user profile.
    return $this->redirect('ngf_user_profile.profile', ['username' => $username]);
  }

  public function followed() {
    $followed_user_items = $this->userManager->getFollowingUsersList();
    $list = [];
    foreach ($followed_user_items as $user_item) {
        $list[] = $this->entityTypeManager()->getViewBuilder($user_item->getEntityTypeId())->view($user_item);
        $list[] = Link::fromTextAndUrl(UserHelper::getUserFullName($user_item), Url::fromRoute('ngf_user_profile.unfollow', ['username' => $user_item->getAccountName()]));
    }

    return $render = [
      '#theme' => 'item_list',
      '#items' => $list,
    ];
  }

  public function addUserListItem($list_id, $username) {
    $this->userManager->addUserListItem($list_id, $username);
    return $this->redirect('ngf_user_profile.user_list_items', ['list_id' => $list_id]);
  }

  public function notifications() {
    $notification_manager = $this->container->get('ngf_user_profile.notification_manager');
    $messages = $notification_manager->getUserNotifications();
    $items = [];
    foreach ($messages as $message) {
      $text = $message->getText();
      $items[] =  \array_shift($text) . ' - <a href="/profile/notification/markasread/' . $message->id() . '">Mark as read</a> - <a href="/profile/notification/remove/' . $message->id() . '">Delete</a><br>';
    }

    return  [
      '#markup' => implode(" - ", $items)
    ];
  }

  public function markAsRead($message_id) {
    $notification_manager = $this->container->get('ngf_user_profile.notification_manager');
    $notification_manager->markNotificationAsRead($message_id);
    return $this->redirect('ngf_user_profile.user_notifications');
  }

  public function removeNotification($message_id) {
    $notification_manager = $this->container->get('ngf_user_profile.notification_manager');
    $notification_manager->removeNotification($message_id);
    return $this->redirect('ngf_user_profile.user_notifications');
  }

  public function interestsSettings() {
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load($this->currentUser()->id());

    return $this->entityFormBuilder()->getForm($user, 'ngf_interests');
  }

}
