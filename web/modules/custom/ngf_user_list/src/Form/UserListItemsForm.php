<?php

namespace Drupal\ngf_user_list\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\ngf_user_list\Manager\UserListManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Defines a form that adds user list item.
 */
class UserListItemsForm extends FormBase {

  use FlagTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * User list manager.
   *
   * @var \Drupal\ngf_user_list\Manager\UserListManager
   */
  protected $userListManager;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager = NULL;

  /**
   * Constructs a Drupal\ngf_user_list\Form\UserListItemForm object.
   *
   * @param \Drupal\flag\FlagService $flag
   *   The flag service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    FlagService $flag,
    UserListManager $userListManager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->flag = $flag;
    $this->userListManager = $userListManager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('ngf_user_list.user_list'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngf_user_list_items';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserList $ngf_user_list = NULL) {
    $form['add'] = [
      '#type' => 'link',
      '#title' => t('Add user'),
      '#url' => Url::fromRoute('ngf_user_list.add_list_item', ['ngf_user_list' => $ngf_user_list->id()]),
      '#attributes' => [
        'class' => [
          'btn btn--blue',
        ]
      ]
    ];

    $list_items = $this->userListManager->getUserListItems($ngf_user_list);
    if (!empty($list_items)) {

      $form['action'] = [
        '#title' => $this->t('Action'),
        '#type' => 'select',
        '#options' => [
          '' => $this->t('Select action'),
          'follow' => $this->t('Follow'),
          'unfollow' => $this->t('Unfollow'),
          'remove' => $this->t('Remove from the list'),
        ],
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ];

      $option_items = [];
      foreach ($list_items as $list_item) {
        $option_items[$list_item->id()] = '';
      }

      $form['feed_items']['list_item_id'] = [
        '#type' => 'checkboxes',
        '#options' => $option_items
      ];

      foreach ($list_items as $list_item) {
        $item_content = [];
        $item_content[] = $this->entityTypeManager
          ->getViewBuilder('user')
          ->view($list_item, 'compact');

        $item_content[] = [
          '#theme' => 'item_list',
          '#items' => [
            Link::fromTextAndUrl(t('Remove user'), Url::fromRoute('ngf_user_list.remove_user_list_item', ['username' => $list_item->getUsername(), 'list_id' => $ngf_user_list->id()])),
          ],
          '#attributes' => [
            'class' => [
              'links inline',
            ]
          ]
        ];

        $form['feed_items']['list_item_id'][$list_item->id()]['#description'] = \Drupal::service('renderer')->render($item_content);
      }
    }
    else {
      $form['nothing'] = [
        '#type' => 'item',
        '#markup' => $this->t('You do not have any users in this list'),
      ];
    }


    $form['list_id'] = [
      '#type' => 'hidden',
      '#value' => $ngf_user_list->id(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $list = UserList::load($form_state->getValue('list_id'));
    $list_item_ids = $form_state->getValue('list_item_id');
    if (!empty($list_item_ids)) {
      foreach ($list_item_ids as $list_item_id) {
        $user = User::load($list_item_id);
        if (empty($user)) {
         $form_state->setErrorByName('list_item_id', $this->t('User is not found.'));
        }
      }
    }
    else {
      $form_state->setErrorByName('list_item_id', $this->t('Select users.'));
    }

    if (empty($list)) {
      $form_state->setErrorByName('list_id', $this->t('List is not found.'));
    } elseif ($list->getOwnerId() !== $this->currentUser()->id()) {
      $form_state->setErrorByName('list_id', $this->t('This list does not belong to you.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $users = User::loadMultiple($form_state->getValue('list_item_id'));
    $action = $form_state->getValue('action');
    $list_id = $form_state->getValue('list_id');

    switch ($action) {
      case 'follow':
        $flag = $this->getFollowUserFlag();
        $current_user = User::Load($this->currentUser()->id());
        $user_names = [];
        foreach ($users as $user) {
          if (!$flag->isFlagged($user, $current_user)) {
            $this->flag->flag($flag, $user, $current_user);
            $user_names[] = $user->getDisplayName();
          }
        }
        drupal_set_message($this->t('You are now following @users', ['@users' => implode(', ', $user_names)]));
        break;

      case 'unfollow':
        $flag = $this->getFollowUserFlag();
        $current_user = User::Load($this->currentUser()->id());
        $user_names = [];
        foreach ($users as $user) {
          if ($flag->isFlagged($user, $current_user)) {
            $this->flag->unflag($flag, $user, $current_user);
            $user_names[] = $user->getDisplayName();
          }
        }
        drupal_set_message($this->t('You are not following @users', ['@users' => implode(', ', $user_names)]));
        break;

      case 'remove':
        $user_names = [];
        foreach ($users as $user) {
          $user_names[] = $user->getDisplayName();
          $this->userListManager->removeUserListItem($list_id, $user->getAccountName());
        }
        drupal_set_message($this->t('Users @users have been removed', ['@users' => implode(', ', $user_names)]));
        break;
    }



    $form_state->setRedirect('ngf_user_list.list_items', ['ngf_user_list' => $list_id]);
  }

}
