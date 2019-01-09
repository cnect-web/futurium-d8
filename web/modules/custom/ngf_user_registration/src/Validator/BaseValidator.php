<?php

namespace Drupal\ngf_user_registration\Validator;

/**
 * Class BaseValidator.
 *
 * @package Drupal\ngf_user_registration\Validator
 */
abstract class BaseValidator implements ValidatorInterface {

  protected $errorMessage;

  public function __construct($error_message = NULL) {
    $this->setErrorMessage($error_message);
  }

  /**
   * {@inheritdoc}
   */
  public function getErrorMessage() {
    return $this->errorMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorMessage($message) {
    return $this->errorMessage = $message;
  }

}
