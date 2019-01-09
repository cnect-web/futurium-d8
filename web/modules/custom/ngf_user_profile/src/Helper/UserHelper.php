<?php

namespace Drupal\ngf_user_profile\Helper;

use Drupal\Core\Session\AccountInterface;


class UserHelper {

  public static function getUserFullName(AccountInterface $user) {
    if (!empty($user->get('field_ngf_first_name')->getString()) && !empty($user->get('field_ngf_first_name')->getString())) {
      return $user->get('field_ngf_first_name')->getString() . ' ' . $user->get('field_ngf_last_name')->getString();
    }
    else {
      return $user->getAccountName();
    }
  }
}