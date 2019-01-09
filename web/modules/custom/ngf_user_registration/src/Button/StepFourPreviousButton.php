<?php

namespace Drupal\ngf_user_registration\Button;

use Drupal\ngf_user_registration\Step\StepsEnum;

/**
 * Class StepFourPreviousButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
class StepFourPreviousButton extends BaseButton {

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'previous';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Previous'),
      '#goto_step' => StepsEnum::STEP_TWO,
      '#skip_validation' => TRUE,
      '#attributes' => [
        'class' => [
          'btn btn--grey btn-large',
        ]
      ]
    ];
  }

}
