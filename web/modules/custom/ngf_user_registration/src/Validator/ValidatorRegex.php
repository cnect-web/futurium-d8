<?php

namespace Drupal\ngf_user_registration\Validator;

/**
 * Class ValidatorRegex.
 *
 * @package Drupal\ngf_user_registration\Validator
 */
class ValidatorRegex extends BaseValidator {

  protected $pattern;

  /**
   * ValidatorRegex constructor.
   *
   * @param string $error_message
   *   Error message.
   * @param string $pattern
   *   Regex pattern.
   */
  public function __construct($pattern, $error_message = null) {
    parent::__construct($error_message);
    $this->pattern = $pattern;
  }

  /**
   * {@inheritdoc}
   */
  public function validates($value) {
    return preg_match($this->pattern, $value);
  }

}
