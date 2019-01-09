<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\ngf_user_registration\Button\StepOneNextButton;
use Drupal\ngf_user_registration\Validator\ValidatorRequired;
use Drupal\ngf_user_registration\Validator\ValidatorUsername;
use Drupal\user\RegisterForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StepOne.
 *
 * @package Drupal\ngf_user_registration\Step
 */
class StepOne extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_ONE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepOneNextButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements(FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Register</h2>'),
    ];

    $fields = [
      [
        'name' => 'first_name',
        'title' => t('First name'),
      ],
      [
        'name' => 'last_name',
        'title' => t('Last name'),
      ],
      [
        'name' => 'username',
        'title' => t('Username'),
      ],
      [
        'name' => 'email',
        'title' => t('Email'),
        'description' => t('All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'),
      ],
    ];

    foreach ($fields as $field) {
      $form[$field['name']] = $this->addTextField($field);
    }

    $form['terms'] = [
      '#prefix' => '<div class="form__block form__block--onecol form__block--ticks"><div class="form__block--checkbox">',
      '#suffix' => '</div></div>',
      '#type' => 'checkbox',
      '#title' => t('<span class="onoffswitch-inner"></span> <span class="label-text">By clicking "Sign Up", you agree to our <a href="@url">Terms and Conditions and that you have read our Data Use Policy</a></span>', [
        '@url' => '',
      ]),
      '#required' => TRUE,
      '#default_value' => $this->getValues()['terms'] ?? NULL,
    ];

    return $form;
  }

  public function addTextField($field) {

    $name = $field['name'] ?? NULL;
    $title = $field['title'] ?? NULL;
    $description = $field['description'] ?? NULL;

    $array = [
      '#type' => 'textfield',
      '#title' => t('@title <span class="form--required text-danger" title="This field is required."><span class="sr-only">Mandatory field</span>*</span>', [
        '@name' => $name,
        '@title' => $title,
      ]),
      '#required' => TRUE,
      '#default_value' => $this->getValues()[$name] ?? NULL,
    ];

    if (!empty($description)) {
      $array['#description'] = $description;
    }

    return $array;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [
      'first_name',
      'last_name',
      'username',
      'email',
      'terms',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [
      'username' => [
        new ValidatorUsername('Only several special characters are allowed, including space, ., -, \, _, @.  ')
      ],
    ];
  }

}

