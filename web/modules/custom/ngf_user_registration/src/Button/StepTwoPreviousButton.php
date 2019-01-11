<?php

namespace Drupal\ngf_user_registration\Button;

use Drupal\ngf_user_registration\Step\StepsEnum;

/**
 * Class StepTwoPreviousButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
class StepTwoPreviousButton extends BaseButton {

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
      '#goto_step' => StepsEnum::STEP_ONE,
      '#skip_validation' => TRUE,
      '#attributes' => [
        'class' => [
          'btn btn--grey btn-large',
        ]
      ]
    ];
  }

}
