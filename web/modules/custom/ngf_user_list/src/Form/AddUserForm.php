<?php

namespace Drupal\ngf_user_list\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\ngf_user_list\Manager\UserListManager;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that adds user list item.
 */
class AddUserForm extends FormBase {

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
   * Constructs a Drupal\ngf_user_list\Form\UserListItemForm object.
   *
   * @param \Drupal\flag\FlagService $flag
   *   The flag service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    FlagService $flag,
    UserListManager $userListManager
  ) {
    $this->flag = $flag;
    $this->userListManager = $userListManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag'),
      $container->get('ngf_user_list.user_list')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngf_add_user';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ngf_user_list = NULL) {
    $user_lists = $this->userListManager->getUserLists();

    $form['user_lists_link'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl(t('Manage your user lists'), Url::fromRoute('ngf_user_list.user_lists'))->toString(),
    ];

    $options = [
      '' => $this->t('Select list')
    ];
    foreach ($user_lists as $user_list) {
      $options[$user_list->id()] = $user_list->getName();
    }
    $form['list_id'] = [
      '#title' => $this->t('User list'),
      '#type' => 'select',
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => $ngf_user_list->id(),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add user'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $list = UserList::load($form_state->getValue('list_id'));
    $user = User::load($form_state->getValue('user_id'));

    if (empty($user)) {
      $form_state->setErrorByName('user_id', $this->t('User is not found.'));
    } elseif (empty($list)) {
      $form_state->setErrorByName('list_id', $this->t('List is not found.'));
    } elseif ($list->getOwnerId() !== $this->currentUser()->id()) {
      $form_state->setErrorByName('list_id', $this->t('This list does not belong to you.'));
    } else {
      $flag = $this->getListItemFlag();
      if ($flag->isFlagged($list, $user)) {
        $form_state->setErrorByName('list_id', $this->t('You already have user @username in the list', ['@username' => $user->getDisplayName()]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = UserList::load($form_state->getValue('list_id'));
    $user = User::load($form_state->getValue('user_id'));

    $flag = $this->getListItemFlag();
    if (!$flag->isFlagged($list, $user)) {
      $this->flag->flag($flag, $list, $user);
      drupal_set_message($this->t('User @username has been added to the list', ['@username' => $user->getDisplayName()]));
    }
    else {
      drupal_set_message($this->t('User @username is already in this list', ['@username' => $user->getDisplayName()]), 'error');
    }

    $form_state->setRedirect('ngf_user_profile.page.user_profile', ['user' => $user->id()]);
  }

}
