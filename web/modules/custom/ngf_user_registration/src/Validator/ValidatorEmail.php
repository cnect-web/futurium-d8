<?php

namespace Drupal\ngf_user_registration\Validator;
/**
 * Class ValidatorEmail.
 *
 * @package Drupal\ngf_user_registration\Validator
 */
class ValidatorEmail extends BaseValidator {
  /**
   * {@inheritdoc}
   */
  public function validates($value) {
    if ($this->isEmailUnique($value)) {
      $this->setErrorMessage(t('Email already exists in the system.'));
      return false;
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmailUnique($email) {
    $query = \Drupal::database()->query('SELECT 1 FROM {users} AS u 
                                          INNER JOIN {users_field_data} AS fud ON u.uid = fud.uid 
                                          WHERE fud.mail = :email', [':email' => $email]);

    return count($query->fetchAll());
  }

}
