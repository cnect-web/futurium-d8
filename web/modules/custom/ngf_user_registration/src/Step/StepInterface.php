<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface StepInterface.
 *
 * @package Drupal\ngf_user_registration\Step
 */
interface StepInterface {

  /**
   * Gets the step.
   *
   * @returns step;
   */
  public function getStep();

  /**
   * Returns a renderable form array that defines a step.
   */
  public function buildStepFormElements(FormStateInterface $form_state);

  /**
   * Returns buttons on step.
   */
  public function getButtons();

  /**
   * All fields name.
   *
   * @returns array of all field names.
   */
  public function getFieldNames();

  /**
   * All field validators.
   *
   * @returns array of fields with their validation requirements.
   */
  public function getFieldsValidators();

  /**
   * Sets filled out values of step.
   */
  public function setValues($values);

  /**
   * Gets filled out values of step.
   */
  public function getValues();

}
