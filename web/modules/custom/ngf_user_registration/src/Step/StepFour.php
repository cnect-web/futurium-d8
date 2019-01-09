<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ngf_user_registration\Button\StepFourFinishButton;
use Drupal\ngf_user_registration\Button\StepFourPreviousButton;
use Drupal\ngf_user_registration\Validator\ValidatorRequired;
use Drupal\ngf_user_profile\UserSettingsManager;

/**
 * Class StepFour.
 *
 * @package Drupal\ngf_user_registration\Step
 */
class StepFour extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_FOUR;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepFourPreviousButton(),
      new StepFourFinishButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements(FormStateInterface $form_state) {

    $form['title'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Private settings</h2>'),
    ];
    $form['info'] = [
      '#type' => 'item',
      '#markup' => t('<p>Private settings information</p>'),
    ];
    $form['setting_wrapper'] = [
      '#type' => 'container',
    ];
    $settings = \Drupal::getContainer()->get('ngf_user_profile.user_settings_manager')->getList();
    foreach ($settings as $key => $setting) {
      $form['setting_wrapper'][$key] = [
        '#prefix' =>  '<div class="form__block--toggle">',
        '#suffix' =>  '</div>',
        '#type' => 'checkbox',
        '#title' => t('<span class="onoffswitch-inner"></span> <span class="label-text">@title</span>',[
          '@title' => $setting['title'],
        ]),
        '#default_value' => $setting['default_value'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return array_keys(\Drupal::getContainer()->get('ngf_user_profile.user_settings_manager')->getList());
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsValidators() {
    return [];
  }

}
