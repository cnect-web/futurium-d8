<?php

namespace Drupal\ngf_user_list\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\ngf_user_list\Entity\UserList;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that adds user list item.
 */
class UserListItemForm extends FormBase {

  use FlagTrait;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * Constructs a Drupal\ngf_user_list\Form\UserListItemForm object.
   *
   * @param \Drupal\flag\FlagService $flag
   *   The flag service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(FlagService $flag) {
    $this->flag = $flag;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flag')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngf_user_list_item';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $ngf_user_list = NULL) {
    $form['user_id'] = [
      '#title' => ('Select User'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#required' => TRUE,
      '#selection_settings' => [
        'include_anonymous' => FALSE,
      ],
    ];

    $form['list_id'] = [
      '#type' => 'hidden',
      '#value' => $ngf_user_list->id(),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
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
    $this->flag->flag($flag, $list, $user);
    drupal_set_message($this->t('User @username has been added to the list', ['@username' => $user->getDisplayName()]));
    $form_state->setRedirect('ngf_user_list.list_items', ['ngf_user_list' => $list->id()]);
  }

}
