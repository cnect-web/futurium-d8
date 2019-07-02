<?php

namespace Drupal\fut_activity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityActivityTrackerForm.
 */
class EntityActivityTrackerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\fut_activity\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity_activity_tracker->label(),
      '#description' => $this->t("Label for the Entity activity tracker."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity_activity_tracker->id(),
      '#machine_name' => [
        'exists' => '\Drupal\fut_activity\Entity\EntityActivityTracker::load',
      ],
      '#disabled' => !$entity_activity_tracker->isNew(),
    ];

    $entity_type_options = [];
    foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
        $entity_type_options[$entity_type_id] = $entity_type->get('label');
      }
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('Select entity type for this config.'),
      '#default_value' => $entity_activity_tracker->getTargetEntityType(),
      '#options' => $entity_type_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'updateBundlesElement'],
        'event' => 'change',
        'wrapper' => 'entity-bundle-wrapper',
      ],
    ];



    $form['entity_bundle_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'entity-bundle-wrapper'],
    ];

    $entity_type = $form_state->getValue('entity_type');
    if (!empty($entity_type)) {
      $form['entity_bundle_wrapper']['entity_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#options' => $this->getBundleOptions($entity_type),
      ];
    }



    $form['decay'] = [
      '#type' => 'number',
      '#title' => $this->t('Decay Value'),
      '#min' => 1,
      '#default_value' => $entity_activity_tracker->getDecay(),
      '#description' => $this->t('The activity value to subtract when preform a decay.'),
      '#required' => TRUE,
    ];

    $form['decay_granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#min' => 1,
      '#default_value' => $entity_activity_tracker->getDecayGranularity(),
      '#description' => $this->t('The time in seconds that the activity value is kept before applying the decay.'),
      '#required' => TRUE,
    ];

    $form['halflife'] = [
      '#type' => 'number',
      '#title' => $this->t('Half-life time'),
      '#min' => 1,
      '#default_value' => $entity_activity_tracker->getHalflife(),
      '#description' => $this->t('The time in seconds in which the activity value halves.'),
      '#required' => TRUE,
    ];

    $form['activity_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on Creation'),
      '#min' => 1,
      '#default_value' => $entity_activity_tracker->getActivityCreation(),
      '#description' => $this->t('The activity value on entity creation.'),
      '#required' => TRUE,
    ];

    $form['activity_update'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on update'),
      '#min' => 1,
      '#default_value' => $entity_activity_tracker->getActivityUpdate(),
      '#description' => $this->t('The activity value on entity update.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);

    /** @var \Drupal\fut_activity\Entity\EntityActivityTrackerInterface $entity_activity_tracker */
    $entity_activity_tracker = $this->entity;


    $status = $entity_activity_tracker->save();

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Entity activity tracker.', [
          '%label' => $entity_activity_tracker->label(),
        ]));
    }
    $form_state->setRedirectUrl($entity_activity_tracker->toUrl('collection'));
  }

  /**
   * updateBundlesElement
   *
   * @param  mixed $form
   * @param  mixed $form_state
   *
   * @return void
   */
  public function updateBundlesElement(array $form, FormStateInterface $form_state) {
    return $form['entity_bundle_wrapper'];
  }

  /**
   * getBundleOptions
   *
   * @param  mixed $entity_type_value
   *
   * @return array
   */
  public function getBundleOptions($entity_type_value) {
    $bundles = \Drupal::entityTypeManager()->getStorage($entity_type_value.'_type')->loadMultiple();

    $bundles_options = [];
    foreach ($bundles as $bundle_id => $bundle_type) {
      $bundles_options[$bundle_id] = $bundle_type->get('name') ?? $bundle_id;
    }

    return $bundles_options;
  }

}
