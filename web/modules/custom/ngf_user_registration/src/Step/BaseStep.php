<?php

namespace Drupal\ngf_user_registration\Step;

/**
 * Class BaseStep.
 *
 * @package Drupal\ngf_user_registration\Step
 */
abstract class BaseStep implements StepInterface {

  /**
   * Multi steps of the form.
   *
   * @var StepInterface
   */
  protected $step;

  /**
   * Values of element.
   *
   * @var array
   */
  protected $values;

  /**
   * BaseStep constructor.
   */
  public function __construct() {
    $this->step = $this->setStep();
  }

  /**
   * {@inheritdoc}
   */
  public function getStep() {
    return $this->step;
  }

  /**
   * {@inheritdoc}
   */
  public function setValues($values) {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValues() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($name, $default_value = NULL) {
    return $this->values[$name] ?? $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  abstract protected function setStep();

  public function addTextField($field) {

    $name = $field['name'] ?? NULL;
    $title = $field['title'] ?? NULL;
    $options = $field['options'] ?? [];

    $array = [
      '#type' => 'textfield',
      '#title' => t('<label for="@name" required>@title <span class="form--required text-danger" title="This field is required."><span class="sr-only">Mandatory field</span>*</span></label>', [
        '@name' => $name,
        '@title' => $title,
      ]),
      '#required' => TRUE,
      '#default_value' => $this->getValues()[$name] ?? NULL,
      '#attributes' => [
        'class' => [
          'form__input form__input--text',
        ]
      ],
    ];

    foreach ($options as $name => $option) {
      $array[$name] = $option;
    }

    return $array;
  }

}
