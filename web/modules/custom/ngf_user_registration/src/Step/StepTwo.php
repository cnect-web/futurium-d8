<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\ngf_user_registration\Button\StepTwoNextButton;
use Drupal\ngf_user_registration\Button\StepTwoPreviousButton;
use Drupal\ngf_user_registration\Validator\ValidatorRequired;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class StepTwo.
 *
 * @package Drupal\ngf_user_registration\Step
 */
class StepTwo extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_TWO;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepTwoPreviousButton(),
      new StepTwoNextButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements(FormStateInterface $form_state) {
    $country = $this->getValue('country', $form_state->getValue('country'));

    $form['title'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Location</h2>'),
    ];

    $form['country'] = [
      '#title' => t('Country'),
      '#type' => 'select',
      '#options' => $this->getCountryOptions(),
      '#default_value' => $country,
      '#ajax' => [
        'callback' => [get_class($this), 'getCity'],
        'event' => 'change',
        'wrapper' => 'city-wrapper',
      ],
    ];

    $form['city_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'city-wrapper'],
    ];

    $city = $this->getValue('city');
    if (!empty($city) || !empty($country)) {
      $form['city_wrapper']['city'] = [
        '#type' => 'textfield',
        '#title' => t('City'),
        '#default_value' => $city,
        '#autocomplete_route_name' => 'ngf_user_registration.city_autocomplete',
        '#autocomplete_route_parameters' => ['country_id' => $country],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [
      'city',
      'country',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [];
  }

  public static function getCity(&$form, FormStateInterface $form_state) {
    return $form['wrapper']['city_wrapper'];
  }

  public function getCountryOptions() {
    $vocabulary_name = 'ngf_countries';
    $countries = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary_name);
    $items = [];
    $items[0] = t('Select country');
    foreach ($countries as $country) {
      $items[$country->tid] = $country->name;
    }

    return $items;
  }

}
