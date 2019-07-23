<?php

namespace Drupal\fut_activity\Plugin\ActivityProcessor;

use Drupal\fut_activity\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\fut_activity\Event\ActivityDecayEvent;
use Drupal\fut_activity\Plugin\ActivityProcessorInterface;
use Drupal\fut_activity\Entity\EntityActivityTrackerInterface;

/**
 * Sets setting for nodes and preforms the activity process for nodes.
 *
 * @ActivityProcessor (
 *   id = "entity_decay",
 *   label = @Translation("Entity Decay")
 * )
 */
class EntityDecay extends ActivityProcessorBase implements ActivityProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'decay' => 100,
    // 4 days;
      'decay_granularity' => 345600,
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
    $this->configuration['decay'] = $form_state->getValue('decay');
    $this->configuration['decay_granularity'] = $form_state->getValue('decay_granularity');
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {

    $replacements = [
      '@plugin_name' => $this->pluginDefinition['label']->render(),
      '@decay' => $this->configuration['decay'],
      '@decay_granularity' => $this->configuration['decay_granularity'],
    ];
    return $this->t('<b>@plugin_name:</b> <br> Decay: @decay <br> Granularity: @decay_granularity <br>', $replacements);
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    $dispatcher_type = $event->getDispatcherType();

    switch ($dispatcher_type) {
      case ActivityDecayEvent::DECAY:
        $records = $this->recordsToDecay($event->getTracker());
        if (!empty($records)) {
          foreach ($records as $record) {
            // Right now i'm simply decreasing the configured decay value.
            // Later on we can work on fancy algorithm to decay entities.
            $record->decreaseActivity($this->configuration['decay']);
            $this->activityRecordStorage->updateActivityRecord($record);
          }
        }
        break;
    }
  }

  /**
   * This returns List of ActivityRecords to Decay.
   *
   * @param \Drupal\fut_activity\EntityActivityTrackerInterface $tracker
   *   The tracker config entity.
   *
   * @return \Drupal\fut_activity\ActivityRecord[]
   *   List of records to decay.
   */
  protected function recordsToDecay(EntityActivityTrackerInterface $tracker) {
    return $this->activityRecordStorage->getActivityRecordsChanged(time() - $this->configuration['decay_granularity'], $tracker->getTargetEntityType(), $tracker->getTargetEntityBundle());
  }

}
