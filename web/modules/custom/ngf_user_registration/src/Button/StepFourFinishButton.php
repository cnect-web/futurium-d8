<?php

namespace Drupal\ngf_user_registration\Button;

use Drupal\ngf_user_registration\Step\StepsEnum;

/**
 * Class StepFourFinishButton.
 *
 * @package Drupal\ngf_user_registration\Button
 */
class StepFourFinishButton extends BaseButton {

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return 'finish';
  }

  public function ajaxify() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'submit',
      '#value' => t('Finish!'),
      '#submit_handler' => 'submitValues',
      '#goto_step' => StepsEnum::STEP_FINALIZE,
      '#attributes' => [
        'class' => [
          'btn btn--green',
        ]
      ]
    ];
  }

}
