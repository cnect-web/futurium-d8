<?php

namespace Drupal\ngf_user_registration\Button;

use Drupal\ngf_user_registration\Step\StepsEnum;

/**
 * Class StepOneNextButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
class StepOneNextButton extends BaseButton {

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
      '#goto_step' => StepsEnum::STEP_TWO,
      '#attributes' => [
        'class' => [
          'btn btn--green',
        ]
      ]
    ];
  }

}
