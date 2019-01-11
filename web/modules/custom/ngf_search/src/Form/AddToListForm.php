<?php

namespace Drupal\ngf_search\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\ngf_user_list\Manager\UserListManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\TempStore\PrivateTempStoreFactory;


use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormState;

use Drupal\redirect\Entity\Redirect;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ngf_user_profile\Manager\UserSettingsManager;
use Drupal\user\UserData;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides location settings form.
 *
 * @package Drupal\ngf_user_registration\Form
 */
class AddToListForm extends ConfirmFormBase {
  use FlagTrait;
  use StringTranslationTrait;

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
   * The temp store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * Class constructor.
   *
   * @param \Drupal\ngf_user_list\Manager\UserListManager $user_list_manager
   *   User list manager.
   * @param Drupal\Core\Entity\EntityTypeManagerInterfac $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   */
  public function __construct(
    UserListManager $user_list_manager,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory,
    FlagService $flag
  ) {
    $this->userListManager = $user_list_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStoreFactory = $temp_store_factory;
    $this->flag = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngf_user_list.user_list'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngf_search_add_to_list';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url($this->getCancelRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRouteName() {
    return 'view.ngf_users_search.user_search';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Select you list in which you want to add users');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {


    $form['list_id'] = [
      '#type' => 'select',
      '#options' => $this->getUserListOptions(),
    ];

    // Retrieve the accounts to be canceled from the temp store.
    /* @var \Drupal\user\Entity\User[] $accounts */
    $accounts = $this->tempStoreFactory
      ->get('ngf_search_user_list_items')
      ->get($this->currentUser()->id());

    if (!$accounts) {
      return $this->redirect($this->getCancelRouteName());
    }

    $names = [];
    $form['user_ids'] = ['#tree' => TRUE];
    foreach ($accounts as $account) {
      $uid = $account->id();
      $names[$uid] = $account->label();
      $form['user_ids'][$uid] = [
        '#type' => 'hidden',
        '#value' => $uid,
      ];
    }

    $form['user_ids']['names'] = [
      '#theme' => 'item_list',
      '#items' => $names,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to list'),
      '#attributes' => [
        'class' => [
          'btn btn--green btn-large',
        ]
      ],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = UserList::load($form_state->getValue('list_id'));
    $users = User::loadMultiple($form_state->getValue('user_ids'));
    $flag = $this->getListItemFlag();
    $user_names = [];
    foreach ($users as $user) {
      if (!$flag->isFlagged($list, $user)) {
        $this->flag->flag($flag, $list, $user);
        $user_names[] = $user->getDisplayName();
      }
    }

    if (!empty($user_names)) {
      drupal_set_message($this->t('Users @usernames is already in this list', ['@usernames' => implode(', ', $user_names)]));
    }
    $form_state->setRedirect($this->getCancelRouteName());
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $list = UserList::load($form_state->getValue('list_id'));
    $user_ids = $form_state->getValue('user_ids');

    $users = [];

    if (!empty($user_ids)) {
      foreach ($user_ids as $user_id) {
        $user = User::load($user_id);
        if (empty($user)) {
          $form_state->setErrorByName('user_ids', $this->t('User is not found.'));
        }
      }
    } else {
      $form_state->setErrorByName('user_ids', $this->t('Users are not selected.'));
    }

    if (empty($list)) {
      $form_state->setErrorByName('list_id', $this->t('List is not found.'));
    } elseif ($list->getOwnerId() !== $this->currentUser()->id()) {
      $form_state->setErrorByName('list_id', $this->t('This list does not belong to you.'));
    } else {
      $flag = $this->getListItemFlag();
      foreach ($users as $user) {
        if ($flag->isFlagged($list, $user)) {
          $form_state->setErrorByName('list_id', $this->t('You already have user @username in the list', ['@username' => $user->getDisplayName()]));
        }
      }
    }
  }

  protected function getUserListOptions() {
    $user_list_options = ['' => $this->t('Select list')];
    $user_lists = $this->userListManager->getUserLists();
    foreach ($user_lists as $user_list) {
      $user_list_options[$user_list->id()] = $user_list->label();
    }

    return $user_list_options;
  }
}


