<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class StepFinalize.
 *
 * @package Drupal\ngf_user_registration\Step
 */
class StepFinalize extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_FINALIZE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements(FormStateInterface $form_state) {

    $form['completed'] = [
      '#markup' => t('<p>You successfully registered</p><p>We have sent an e-mail with further instructions</p><p><a href="/">Return to homepage</a></p>'),
    ];

    return $form;
  }

}
