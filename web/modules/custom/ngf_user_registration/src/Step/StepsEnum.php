<?php

namespace Drupal\ngf_user_registration\Step;

/**
 * Class StepsEnum.
 *
 * @package Drupal\ngf_user_registration\Step
 */
abstract class StepsEnum {

  /**
   * Steps used in form.
   */
  const STEP_ONE = 1;
  const STEP_TWO = 2;
  const STEP_THREE = 3;
  const STEP_FOUR = 4;
  const STEP_FIVE = 5;
  const STEP_FINALIZE = 6;

  /**
   * Return steps associative array.
   *
   * @return array
   *   Associative array of steps.
   */
  public static function toArray() {
    return [
      self::STEP_ONE => 'step-one',
      self::STEP_TWO => 'step-two',
      self::STEP_THREE => 'step-three',
      self::STEP_FOUR => 'step-four',
      self::STEP_FIVE => 'step-five',
      self::STEP_FINALIZE => 'step-final',
    ];
  }

  /**
   * Map steps to it's class.
   *
   * @param int $step
   *   Step number.
   *
   * @return bool
   *   Return true if exist.
   */
  public static function map($step) {
    $map = [
      self::STEP_ONE => 'Drupal\\ngf_user_registration\\Step\\StepOne',
      self::STEP_TWO => 'Drupal\\ngf_user_registration\\Step\\StepTwo',
      self::STEP_THREE => 'Drupal\\ngf_user_registration\\Step\\StepThree',
      self::STEP_FOUR => 'Drupal\\ngf_user_registration\\Step\\StepFour',
      self::STEP_FIVE => 'Drupal\\ngf_user_registration\\Step\\StepFive',
      self::STEP_FINALIZE => 'Drupal\\ngf_user_registration\\Step\\StepFinalize',
    ];

    return isset($map[$step]) ? $map[$step] : FALSE;
  }

}
