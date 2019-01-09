<?php

namespace Drupal\ngf_user_registration\Validator;
/**
 * Class ValidatorUsername.
 *
 * @package Drupal\ngf_user_registration\Validator
 */
class ValidatorUsername extends BaseValidator {
  /**
   * {@inheritdoc}
   */
  public function validates($value) {
    $error_message = user_validate_name($value);
    if (!empty($error_message)) {
      $this->setErrorMessage($error_message);
      return false;
    }
    else if ($this->isUserNameUnique($value)) {
      $this->setErrorMessage(t('Username already exists in the system.'));
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isUserNameUnique($user_name) {
    $query = \Drupal::database()->query('SELECT 1 FROM {users} AS u 
                                          INNER JOIN {users_field_data} AS fud ON u.uid = fud.uid 
                                          WHERE fud.name = :name', [':name' => $user_name]);

    return count($query->fetchAll());
  }

}
