<?php

namespace Drupal\fut_activity\Plugin\ActivityProcessor;

use Drupal\fut_activity\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\fut_activity\ActivityRecord;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "activity_processor_node",
 *   label = @Translation("Node Processor")
 * )
 *
 */
class ActivityProcessorNode extends ActivityProcessorBase implements ContainerFactoryPluginInterface {

   /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'decay' => 100,
      'decay_granularity' => 345600, // 4 days;
      'halflife' =>  172800, // 2 days;
      'activity_creation' => 5000,
      'activity_update' => 100,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {


    $form['decay'] = [
      '#type' => 'number',
      '#title' => $this->t('Decay Value'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['decay'],
      '#description' => $this->t('The activity value to subtract when preform a decay.'),
      '#required' => TRUE,
    ];

    $form['decay_granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['decay_granularity'],
      '#description' => $this->t('The time in seconds that the activity value is kept before applying the decay.'),
      '#required' => TRUE,
    ];

    $form['halflife'] = [
      '#type' => 'number',
      '#title' => $this->t('Half-life time'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['halflife'],
      '#description' => $this->t('The time in seconds in which the activity value halves.'),
      '#required' => TRUE,
    ];

    $form['activity_creation'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on Creation'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_creation'],
      '#description' => $this->t('The activity value on entity creation.'),
      '#required' => TRUE,
    ];

    $form['activity_update'] = [
      '#type' => 'number',
      '#title' => $this->t('Activity on update'),
      '#min' => 1,
      '#default_value' => $this->getConfiguration()['activity_update'],
      '#description' => $this->t('The activity value on entity update.'),
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
    $this->configuration['decay'] = $form_state->getValue('decay');
    $this->configuration['decay_granularity'] = $form_state->getValue('decay_granularity');
    $this->configuration['halflife'] = $form_state->getValue('halflife');
    $this->configuration['activity_creation'] = $form_state->getValue('activity_creation');
    $this->configuration['activity_update'] = $form_state->getValue('activity_update');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {

    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@decay' => $this->configuration['decay'],
      '@decay_granularity' => $this->configuration['decay_granularity'],
      '@halflife' => $this->configuration['halflife'],
      '@activity_creation' => $this->configuration['activity_creation'],
      '@activity_update' => $this->configuration['activity_update'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Decay: @decay <br> Granularity: @decay_granularity <br> Half Life: @halflife <br>  Activity on creation: @activity_creation <br> Activity on update: @activity_update <br>', $replacements );
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {

    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case HookEventDispatcherInterface::ENTITY_INSERT:
        $entity = $event->getEntity();
        $activity_record = new ActivityRecord($entity->getEntityTypeId(),$entity->id(),$this->configuration['activity_creation']);
        $this->activityRecordStorage->createActivityRecord($activity_record);
      break;

      case HookEventDispatcherInterface::ENTITY_UPDATE:
        /** @var \Drupal\fut_activity\ActivityRecord $activity_record */
        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($event->getEntity());
        $activity_record->increaseActivity($this->configuration['activity_update']);
        $this->activityRecordStorage->updateActivityRecord($activity_record);
      break;

      case HookEventDispatcherInterface::ENTITY_DELETE:
        /** @var \Drupal\fut_activity\ActivityRecord $activity_record */
        $activity_record = $this->activityRecordStorage->getActivityRecordByEntity($event->getEntity());
        $this->activityRecordStorage->deleteActivityRecord($activity_record);
      break;

      case ActivityDecayEvent::DECAY:
        $records = $this->recordsToDecay();
        foreach ($records as $record) {
          $record->decreaseActivity($this->configuration['decay']);
          $this->activityRecordStorage->updateActivityRecord($record);
        }
      break;

    }

  }

  protected function recordsToDecay() {
    return $this->activityRecordStorage->getActivityRecordsChanged(time() - $this->configuration['decay_granularity']);
  }

}
