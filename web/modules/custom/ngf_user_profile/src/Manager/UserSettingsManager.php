<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserDataInterface;

class UserSettingsManager {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * UserSettingsManager constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data service.
   */
  public function __construct(AccountInterface $current_user, UserDataInterface $userData) {
    $this->currentUser = $current_user;
    $this->userData = $userData;
  }

  public function setSetting($name, $value) {
    $this->userData->set('ngf_user_profile', $this->currentUser->id(), $name, $value);
  }

  public function getSetting($name) {
    return $this->userData->get('ngf_user_profile', $this->currentUser->id(), $name);
  }

  public function setActionContact() {
    $this->setSetting('action_contact');
  }

  public function getActionContact($value) {
    $this->getSetting('action_contact', $value);
  }

  public function setActionInviteToEvent() {
    $this->setSetting('action_invite_to_event');
  }

  public function getActionInviteToEvent($value) {
    $this->setSetting('action_invite_to_event', $value);
  }

  public function setActionInviteToGroup() {
    $this->setSetting('action_invite_to_group');
  }

  public function getActionInviteToGroup($value) {
    $this->setSetting('action_invite_to_group', $value);
  }

  public function setSearchEmail() {
    $this->setSetting('search_email');
  }

  public function getSearchEmail($value) {
    $this->getSetting('search_email', $value);
  }

  public function setSearchInterests() {
    $this->setSetting('search_interests');
  }

  public function getSearchInterests($value) {
    $this->getSetting('search_interests', $value);
  }

  public function setSearchLocation() {
    $this->setSetting('search_location');
  }

  public function getSearchLocation($value) {
    $this->getSetting('search_location', $value);
  }

  public function setSearchName() {
    $this->setSetting('search_name');
  }

  public function getSearchName($value) {
    $this->getSetting('search_name', $value);
  }

  public function setSearchPosition() {
    $this->setSetting('search_position');
  }

  public function getSearchPosition($value) {
    $this->getSetting('search_position', $value);
  }

  public function getList () {
    return [
      'action_contact' => [
        'default_value' => 1,
        'title' => t('Contact you'),
      ],
      'action_invite_to_event' => [
        'default_value' => 1,
        'title' => t('Invite you to groups'),
      ],
      'action_invite_to_group' => [
        'default_value' => 1,
        'title' => t('Invite you to events'),
      ],
      'search_email' => [
        'default_value' => 1,
        'title' => t('Your email'),
      ],
      'search_interests' => [
        'default_value' => 1,
        'title' => t('Your interests'),
      ],
      'search_location' => [
        'default_value' => 0,
        'title' => t('Your location'),
      ],
      'search_name' => [
        'default_value' => 1,
        'title' => t('Your name'),
      ],
    ];
  }


}

