<?php

namespace Drupal\fut_activity\Plugin\ActivityProcessor;

use Drupal\fut_activity\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\fut_activity\ActivityRecord;
use Drupal\fut_activity\Event\TrackerCreateEvent;
use Drupal\fut_activity\Plugin\ActivityProcessorInterface;
use Drupal\fut_activity\ActivityRecordStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\fut_activity\Event\TrackerDeleteEvent;

/**
 * Sets activity when entity is created.
 *
 * @ActivityProcessor (
 *   id = "entity_create",
 *   label = @Translation("Entity Create")
 * )
 *
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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // do nodthing for now.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['activity_creation'] = $form_state->getValue('activity_creation');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@activity_creation' => $this->configuration['activity_creation'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Activity on creation: @activity_creation <br>', $replacements );
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
        foreach ($this->getExistingEntities($event->getTracker()) as $existing_entity) {
          $activity_record = new ActivityRecord($existing_entity->getEntityTypeId(), $existing_entity->bundle(), $existing_entity->id(), $this->configuration['activity_creation']);
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
        foreach ($this->activityRecordStorage->getActivityRecords($tracker->getTargetEntityType(),$tracker->getTargetEntityBundle()) as $activity_record) {
          $this->activityRecordStorage->deleteActivityRecord($activity_record);
        }
        break;
    }
  }

  /**
   * This returns List of ActivityRecords to Decay.
   *
   * @return \Drupal\fut_activity\ActivityRecord[]
   */
  protected function recordsToDecay($tracker) {
    return $this->activityRecordStorage->getActivityRecordsChanged(time() - $this->configuration['decay_granularity'], $tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
  }

  /**
   * getExistingEntities
   *
   * @param  mixed $tracker
   *
   * @return array
   */
  protected function getExistingEntities($tracker) {
    $storage = $this->entityTypeManager->getStorage($tracker->getTargetEntityType());
    $bundle_key = $storage->getEntityType()->getKey('bundle');
    return $this->entityTypeManager->getStorage($tracker->getTargetEntityType())->loadByProperties([$bundle_key => $tracker->getTargetEntityBundle()]);
  }

}
