<?php

namespace Drupal\fut_activity;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Defines FutActivityFormsOperations class to react to form related operaions.
 */
class FutActivityFormsOperations implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\fut_activity\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new FutActivityFormsOperations instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\fut_activity\ActivityRecordStorageInterface $activity_record_storage
   *   The activity record storage service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ActivityRecordStorageInterface $activity_record_storage, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->activityRecordStorage = $activity_record_storage;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('fut_activity.activity_record_storage'),
      $container->get('messenger')
    );
  }

  /**
   * Expose Activity field to tracked entities.
   *
   * @return array
   *   Associative array exposing activity pseudo-field to tracked entity forms.
   *
   * @see hook_entity_extra_field_info()
   */
  public function entityExtraFieldInfo() {
    $extra_activity_fields = [];
    foreach ($this->getTrackers() as $tracker) {
      $extra_activity_fields[$tracker->getTargetEntityType()][$tracker->getTargetEntityBundle()] = [
        'form' => [
          'activity' => [
            'label' => $this->t('Activity'),
            'description' => $this->t('Activity field'),
            'weight' => 999,
          ],
        ],
      ];
    }
    return $extra_activity_fields;
  }

  /**
   * Alters forms of tracked bundles to show a activity field.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $form_id
   *   The form id.
   *
   * @see hook_form_alter()
   */
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();
    if ($entity instanceof ContentEntityInterface) {
      if (!$entity->isNew() && $this->hasTracker($entity)) {
        $form['activity'] = [
          '#type' => 'details',
          '#title' => $this->t('Activity'),
          '#description' => $this->t('Set (force) a activity value to this entity.'),
          '#open' => FALSE,
          'activity_tracker_link' => [
            '#title' => $this->t('Edit @tracker', ['@tracker' => $this->getTracker($entity)->label()]),
            '#type' => 'link',
            '#url' => $this->getTrackerCanonical($entity),
          ],
          'activity_value' => [
            '#type' => 'number',
            '#title' => $this->t('Activity Value'),
            '#min' => 1,
            '#default_value' => $this->getActivityValue($entity),
          ],
          'submit' => [
            '#type' => 'submit',
            '#value' => $this->t('Set Activity'),
            '#submit' => [
              [$this, 'activitySubmit'],
            ],
          ],
        ];
      }
    }
  }

  /**
   * Custom submit handler to save activity value.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function activitySubmit(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->getEntity();
    $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($entity);

    // Only update if value is different that what it was.
    if ($form_state->getValue('activity_value') != $activity_record->getActivityValue()) {
      $activity_record->setActivityValue($form_state->getValue('activity_value'));
      $this->activityRecordStorage->updateActivityRecord($activity_record);
      $this->messenger->addMessage($this->t('Activity was updated.'));
      $form_state->setRedirectUrl($entity->toUrl());
    }
  }

  /**
   * Get all existing EntityActivityTrackers.
   *
   * @return \Drupal\fut_activity\Entity\EntityActivityTrackerInterface[]
   *   Array containing all trackers config entities.
   */
  protected function getTrackers() {
    return $this->entityTypeManager->getStorage('entity_activity_tracker')->loadMultiple();
  }

  /**
   * Check if exists EntityActivityTracker for given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check if has a tracker.
   *
   * @return bool
   *   Returns TRUE if there is a tracker.
   */
  protected function hasTracker(ContentEntityInterface $entity) {
    if (!empty($this->getTracker($entity))) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get Tracker given a entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return \Drupal\fut_activity\Entity\EntityActivityTrackerInterface
   *   The tracker.
   */
  protected function getTracker(ContentEntityInterface $entity) {
    $properties = [
      'entity_type' => $entity->getEntityTypeId(),
      'entity_bundle' => $entity->bundle(),
    ];
    $tracker = $this->entityTypeManager->getStorage('entity_activity_tracker')->loadByProperties($properties);
    return reset($tracker);
  }

  /**
   * Get current activity value of given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return int
   *   The activity value of $entity.
   */
  protected function getActivityValue(ContentEntityInterface $entity) {
    return $this->activityRecordStorage->getActivityRecordByEntity($entity)->getActivityValue();
  }

  /**
   * Get tracker canonical url given tracked entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The tracked entity.
   *
   * @return \Drupal\Core\Url
   *   The URL to tracker canonical route.
   */
  protected function getTrackerCanonical(ContentEntityInterface $entity) {
    return $this->getTracker($entity)->toUrl();
  }

}
