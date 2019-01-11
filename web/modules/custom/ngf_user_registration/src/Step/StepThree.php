<?php

namespace Drupal\ngf_user_registration\Step;

use Drupal\ngf_user_registration\Button\StepThreeNextButton;
use Drupal\ngf_user_registration\Button\StepThreePreviousButton;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class StepThree.
 *
 * @package Drupal\ngf_user_registration\Step
 */
class StepThree extends BaseStep {

  /**
   * {@inheritdoc}
   */
  protected function setStep() {
    return StepsEnum::STEP_THREE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      new StepThreePreviousButton(),
      new StepThreeNextButton(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildStepFormElements(FormStateInterface $form_state) {
    $form = [];
    $form['title'] = [
      '#type' => 'item',
      '#markup' => t('<h2>Interests</h2>'),
    ];
    $form['introtext'] = [
      '#type' => 'item',
      '#markup' => t('<p class="intro-text">Now, what makes you tick? Select or add your interests, it helps us offer you the most relevant content (real content, no adds).</p>'),
    ];
    $form['interests_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'interests-wrapper'],
      '#tree' => TRUE,
    ];

    $interests_items = $this->getValue('interests_items', $form_state->get('interests_items'));
    if (empty($interests_items)) {
      $interests_items = 1;
      $form_state->set('interests_items', $interests_items);
    }

    for ($i = 0; $i < $interests_items; $i++) {
      $form['interests_wrapper']['interests'][$i] = [
        '#prefix' => '<div class="form__block form__block--text">',
        '#suffix' => '</div>',
        '#type' => 'entity_autocomplete',
        '#target_type' => 'taxonomy_term',
        '#selection_settings' => [
          'include_anonymous' => FALSE,
          'target_bundles' => ['ngf_interests'],
        ],
        '#attributes' => [
          'class' => [
            'form__input form__input--text',
          ]
        ],
      ];

      $interest = $this->getValue('interests_wrapper')['interests'][$i] ?? NULL;
      if (!empty($interest) && $term = Term::Load($interest)) {
        $form['interests_wrapper']['interests'][$i]['#default_value'] = $term;
      }
    }

    $form['interests_wrapper']['add_item'] = [
      '#prefix' => t('examples: Green energy, Local democracy, Open source'),
      '#type' => 'submit',
      '#name' => 'add_item',
      '#value' => t('Add interest'),
      '#submit' => [[get_class($this), 'interestsAddItem']],
      '#ajax' => [
        'callback' => [get_class($this), 'getInterestField'],
        'wrapper' => 'interests-wrapper',
        'progress' => [
          'message' => '',
        ],
      ],
      '#attributes' => [
        'class' => [
          'btn btn--blue',
        ]
      ]
    ];

    return $form;
  }

  /**
   * Ajax Callback for the form.
   *
   * @param array $form
   *   The form being passed in
   * @param array $form_state
   *   The form state
   *
   * @return array
   *   The form element we are changing via ajax
   */
  public static function getInterestField(&$form, FormStateInterface $form_state) {
    return $form['wrapper']['interests_wrapper'];
  }

  /**
   * Functionality for our ajax callback.
   *
   * @param array $form
   *   The form being passed in
   * @param array $form_state
   *   The form state, passed by reference so we can modify
   */
  public static function interestsAddItem(&$form, FormStateInterface $form_state) {
    $interests_items = $form_state->get('interests_items');
    $form_state->set('interests_items', ++$interests_items);
    $form_state->setRebuild();

  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {
    return [
      'interests_wrapper',
    ];
  }
}
