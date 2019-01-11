<?php

namespace Drupal\ngf_user_registration\Form;

use Drupal\Component\Utility\Tags;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ngf_user_registration\Manager\StepManager;
use Drupal\ngf_user_registration\Step\StepsEnum;
use Drupal\redirect\Entity\Redirect;
use Drupal\user\RoleInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Provides multi step ajax example form.
 *
 * @package Drupal\ngf_user_registration\Form
 */
class UserRegistrationForm extends FormBase {
  use StringTranslationTrait;

  /**
   * Step Id.
   *
   * @var \Drupal\ngf_user_registration\Step\StepsEnum
   */
  protected $stepId;

  /**
   * Multi steps of the form.
   *
   * @var \Drupal\ngf_user_registration\Step\StepInterface
   */
  protected $step;

  /**
   * Step manager instance.
   *
   * @var \Drupal\ngf_user_registration\Manager\StepManager
   */
  protected $stepManager;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $this->stepId = StepsEnum::STEP_ONE;
    $this->stepManager = new StepManager();
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ngf_user_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['wrapper-messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'messages-wrapper',
      ],
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-wrapper',
      ],
    ];

    // Get step from step manager.
    $this->step = $this->stepManager->getStep($this->stepId);

    // Attach step form elements.
    $form['wrapper'] += $this->step->buildStepFormElements($form_state);

    // Attach buttons.
    $form['wrapper']['actions']['#type'] = 'actions';

    $buttons = $this->step->getButtons();
    foreach ($buttons as $button) {
      /** @var \Drupal\ngf_user_registration\Button\ButtonInterface $button */
      $form['wrapper']['actions'][$button->getKey()] = $button->build();

      if ($button->ajaxify()) {
        // Add ajax to button.
        $form['wrapper']['actions'][$button->getKey()]['#ajax'] = [
          'callback' => [$this, 'loadStep'],
          'wrapper' => 'form-wrapper',
          'effect' => 'fade',
        ];
      }

      $callable = [$this, $button->getSubmitHandler()];
      if ($button->getSubmitHandler() && is_callable($callable)) {
        // Attach submit handler to button, so we can execute it later on..
        $form['wrapper']['actions'][$button->getKey()]['#submit_handler'] = $button->getSubmitHandler();
      }
    }

    return $form;

  }

  /**
   * Ajax callback to load new step.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function loadStep(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $messages = drupal_get_messages();
    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => $this->t('Status message'),
          'error' => $this->t('Error message'),
          'warning' => $this->t('Warning message'),
        ],
      ];
      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
    }
    else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }

    // Update Form.
    $response->addCommand(new HtmlCommand('#form-wrapper', $form['wrapper']));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // Only validate if validation doesn't have to be skipped.
    // For example on "previous" button.
    if (empty($triggering_element['#skip_validation']) && $fields_validators = $this->step->getFieldsValidators()) {
      // Validate fields.
      foreach ($fields_validators as $field => $validators) {
        // Validate all validators for field.
        $field_value = $form_state->getValue($field);
        foreach ($validators as $validator) {
          if (!$validator->validates($field_value)) {
            $form_state->setErrorByName($field, $validator->getErrorMessage());
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Save filled values to step. So we can use them as default_value later on.
    $values = [];
    foreach ($this->step->getFieldNames() as $name) {
      $values[$name] = $form_state->getValue($name);
    }

    $this->step->setValues($values);
    // Add step to manager.
    $this->stepManager->addStep($this->step);
    // Set step to navigate to.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#goto_step'])) {
      $this->stepId = $triggering_element['#goto_step'];
    }

    // If an extra submit handler is set, execute it.
    // We already tested if it is callable before.
    if (!empty($triggering_element['#submit_handler'])) {
      $this->{$triggering_element['#submit_handler']}($form, $form_state);
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit handler for last step of form.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state interface.
   */
  public function submitValues(array &$form, FormStateInterface $form_state) {

    // First step.
    $first_step = $this->stepManager->getStep(StepsEnum::STEP_ONE);
    $first_name = $first_step->getValue('first_name');
    $last_name = $first_step->getValue('last_name');
    $username = $first_step->getValue('username');
    $email = $first_step->getValue('email');

    // Second step
    $step_two = $this->stepManager->getStep(StepsEnum::STEP_TWO);
    $country = $step_two->getValue('country');
    $city = EntityAutocomplete::extractEntityIdFromAutocompleteInput($step_two->getValue('city'));

    // Third step
    $interests = $this->stepManager->getStep(StepsEnum::STEP_THREE)->getValue('interests_wrapper')['interests'] ?? [];

    $user = \Drupal\user\Entity\User::create();
    $user->setPassword(user_password());
    $user->enforceIsNew();
    $user->setEmail($email);
    $user->setUsername($username);
    $user->set('init', $email);
    $user->set('field_ngf_first_name', $first_name);
    $user->set('field_ngf_last_name', $last_name);
    $user->set('field_ngf_interests', $interests);
    $user->set('field_ngf_country', $country);
    $user->set('field_ngf_city', $city);
    $user->addRole(null);
    $user->activate();

    // Call Drupal original submission code.
    $formObject = \Drupal::entityTypeManager()->getFormObject('user','register');

    $formStateObject = new FormState();
    $formStateObject->setFormObject($formObject)->disableRedirect();
    $formObject->setEntity($user);
    $form = $formObject->buildForm(['#build_id' => 'register'], $formStateObject);
    $formStateObject->setValue('roles', []);
    $formObject->validateForm($form, $formStateObject);

    $formObject->save($form, $formStateObject);
    $user = $formObject->getEntity();

    // Set additional settings.
    $forth_step = $this->stepManager->getStep(StepsEnum::STEP_FOUR);
    $userData = \Drupal::service('user.data');
    $userSettings = \Drupal::getContainer()->get('ngf_user_profile.user_settings_manager');
    foreach (array_keys($userSettings->getList()) as $setting) {
      $userData->set('ngf', $user->id(), $setting, $forth_step->getValue($setting, 0));
    }

    \Drupal::getContainer()->get('messenger')->addMessage(t('User has been successfully registered'));
  }

}
