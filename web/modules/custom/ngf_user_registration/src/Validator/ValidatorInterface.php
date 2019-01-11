<?php

namespace Drupal\ngf_user_registration\Validator;

/**
 * Interface ValidatorInterface.
 *
 * @package Drupal\ngf_user_registration\Validator
 */
interface ValidatorInterface {

  /**
   * Returns bool indicating if validation is ok.
   */
  public function validates($value);

  /**
   * Returns error message.
   */
  public function getErrorMessage();

}
