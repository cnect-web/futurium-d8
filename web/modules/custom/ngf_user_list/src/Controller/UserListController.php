<?php

namespace Drupal\ngf_user_list\Controller;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\ngf_user_list\Manager\UserListManager;
use Drupal\ngf_user_profile\Controller\UserProfileControllerBase;
use Drupal\user\Entity\AccountInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Discover page controller.
 */
class UserListController extends UserProfileControllerBase {

  /**
   * User list manager.
   *
   * @var \Drupal\ngf_user_list\Manager\UserListManager
   */
  protected $userListManager;

  public function __construct(UserListManager $userListManager) {
    $this->userListManager = $userListManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngf_user_list.user_list')
    );
  }

  protected function getCurrentUserAccount() {
    if (empty($this->currentUserAccount)) {
      $this->currentUserAccount = User::load($this->currentUser()->id());
    }
    return $this->currentUserAccount;
  }

  protected function getUserList($users, $no_items_text) {
    $items = [];
    foreach ($users as $user) {
      $items[] = $this->entityTypeManager()->getViewBuilder('user')->view($user, 'compact');
    }

    return count($items) > 0  ? $items : $this->getRenderMarkup($no_items_text);
  }

  public function userLists() {
    $lists = $this->userListManager->getUserLists();
    $items = [];
    $this->setPageTitle($this->t('Your lists'));
    $items[] = [
      '#type' => 'link',
      '#title' => t('Add a list'),
      '#url' => Url::fromRoute('ngf_user_list.add_user_list'),
      '#attributes' => [
        'class' => [
          'btn btn--blue',
        ]
      ]
    ];
    foreach ($lists as $list) {
      $items[] = [
        '#theme' => 'short_list_item',
        '#title' => $list->getName(),
        '#context' => [
          '#theme' => 'item_list',
          '#items' => [
            Link::fromTextAndUrl('List items', Url::fromRoute('ngf_user_list.list_items', ['ngf_user_list' => $list->id()])),
            Link::fromTextAndUrl('Edit list', Url::fromRoute('ngf_user_list.edit_user_list', ['ngf_user_list' => $list->id()])),
            Link::fromTextAndUrl('Remove list', Url::fromRoute('ngf_user_list.delete_user_list', ['ngf_user_list' => $list->id()])),
          ],
          '#attributes' => [
            'class' => [
              'links inline',
            ]
          ]
        ]
      ];
    }

    return $this->getContent($items);
  }

  public function userListSettings() {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => 'User list settings',
    ];
  }

  public function deleteUserList($ngf_user_list) {
    $this->setPageTitle($this->t('Delete list %title', ['%title' => $ngf_user_list->getName()]));
    return $this->getContent($this->entityFormBuilder()->getForm($ngf_user_list, 'delete'));
  }

  public function removeUserList($list_id) {
    $this->userListManager->removeUserList($list_id);
    // Redirect back to a user lists.
    return $this->redirect('ngf_user_profile.user_lists');
  }


  public function addUserList($name) {
    $this->userListManager->addUserList($name);
    // Redirect back to a user lists.
    return $this->redirect('ngf_user_profile.user_lists');
  }

  public function userListForm(UserList $ngf_user_list = NULL) {
    if (empty($ngf_user_list)) {
      $ngf_user_list = UserList::create();
      $this->setPageTitle($this->t('Add a new list'));
    }
    else {
      $this->setPageTitle($this->t('Edit list'));
    }
    return $this->getContent($this->entityFormBuilder()->getForm($ngf_user_list, 'default'));
  }

  public function removeUserListItem($list_id, $username) {
    $this->userListManager->removeUserListItem($list_id, $username);
    // Redirect back to a user lists.
    return $this->redirect('ngf_user_list.list_items', ['ngf_user_list' => $list_id]);
  }

  public function addUserListItem($list_id, $username) {
    $this->userListManager->addUserListItem($list_id, $username);
    return $this->redirect('ngf_user_list.list_items', ['ngf_user_list' => $list_id]);
  }

  public function userListItemsForm(UserList $ngf_user_list) {
    $this->setPageTitle($this->t('%title list items', ['%title' => $ngf_user_list->getName()]));
    return $this->getContent($this->formBuilder()->getForm('Drupal\ngf_user_list\Form\UserListItemsForm', $ngf_user_list));
  }

  public function userListItemForm(UserList $ngf_user_list) {
    $this->setPageTitle($this->t('Add user to %title', ['%title' => $ngf_user_list->getName()]));
    return $this->getContent($this->formBuilder()->getForm('Drupal\ngf_user_list\Form\UserListItemForm', $ngf_user_list));
  }

  public function AddUserForm(User $user) {
    return $this->formBuilder()->getForm('Drupal\ngf_user_list\Form\AddUserForm', $user);
  }

}
