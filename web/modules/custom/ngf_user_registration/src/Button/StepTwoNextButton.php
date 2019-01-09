<?php

namespace Drupal\ngf_user_registration\Button;

use Drupal\ngf_user_registration\Step\StepsEnum;

/**
 * Class StepTwoNextButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
class StepTwoNextButton extends BaseButton {

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'next';
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Next'),
      '#goto_step' => StepsEnum::STEP_FOUR,
      '#attributes' => [
        'class' => [
          'btn btn--green',
        ]
      ]
    ];
  }

}
