<?php

namespace Drupal\fut_activity\Plugin\ActivityProcessor;

use Drupal\fut_activity\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\ActivityRecord;
use Drupal\fut_activity\Event\TrackerCreateEvent;
use Drupal\fut_activity\Plugin\ActivityProcessorInterface;
use Drupal\fut_activity\ActivityRecordStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_activity\Event\TrackerDeleteEvent;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

/**
 * Sets activity when entity is created.
 *
 * @ActivityProcessor (
 *   id = "entity_create",
 *   label = @Translation("Entity Create")
 * )
 */
class EntityCreate extends ActivityProcessorBase implements ActivityProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityRecordStorageInterface $activity_record_storage, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $activity_record_storage);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('fut_activity.activity_record_storage'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'activity_creation' => 5000,
      'activity_existing' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['activity_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on Creation'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_creation'],
      '#description' => $this->t('The activity value on entity creation.'),
      '#required' => TRUE,
    ];

    $form['activity_existing_enabler'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply different activity value for entities that were already created'),

    ];

    $form['activity_existing'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity for existing entities'),
      '#description' => $this->t('Apply different activity value for entities that were already created. (This just applies on creation process)'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_existing'],
      '#description' => $this->t('The activity value on entity creation.'),
      '#states' => [
        'invisible' => [
          ':input[name="activity_processors[entity_create][settings][activity_existing_enabler]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nodthing for now.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_creation'] = $form_state->getValue('activity_creation');
    $this->configuration['activity_existing_enabler'] = $form_state->getValue('activity_existing_enabler');
    $this->configuration['activity_existing'] = $form_state->getValue('activity_existing');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@activity_creation' => $this->configuration['activity_creation'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Activity on creation: @activity_creation <br>', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {

    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case HookEventDispatcherInterface::ENTITY_INSERT:
        $entity = $event->getEntity();
        $activity_record = new ActivityRecord($entity->getEntityTypeId(), $entity->bundle(), $entity->id(), $this->configuration['activity_creation']);
        $this->activityRecordStorage->createActivityRecord($activity_record);
        break;

      case TrackerCreateEvent::TRACKER_CREATE:
        // Iterate all already existing entities and create a record.
        $activity = ($this->configuration['activity_existing_enabler']) ? $this->configuration['activity_existing'] : $this->configuration['activity_creation'] ;
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {
          $activity_record = new ActivityRecord($existing_entity->getEntityTypeId(), $existing_entity->bundle(), $existing_entity->id(), $activity);
          $this->activityRecordStorage->createActivityRecord($activity_record);
        }
        break;

      case HookEventDispatcherInterface::ENTITY_DELETE:
        /** @var \Drupal\fut_activity\ActivityRecord $activity_record */
        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($event->getEntity());
        $this->activityRecordStorage->deleteActivityRecord($activity_record);
        break;

      case TrackerDeleteEvent::TRACKER_DELETE:
        $tracker = $event->getTracker();
        // Get ActivityRecords from this tracker.
        foreach ($this->activityRecordStorage->getActivityRecords($tracker->getTargetEntityType(), $tracker->getTargetEntityBundle()) as $activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;
    }
  }

  /**
   * Get existing entities of tracker that was just created.
   *
   * @param \Drupal\fut_activity\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   Existing entities to be tracked.
   */
  protected function getExistingEntities(EntityActivityTrackerInterface $tracker) {
    $storage = $this->entityTypeManager->getStorage($tracker->getTargetEntityType());
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
  }

}
