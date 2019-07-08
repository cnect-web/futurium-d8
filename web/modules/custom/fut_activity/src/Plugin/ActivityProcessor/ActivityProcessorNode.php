<?php

namespace Drupal\fut_activity\Plugin\ActivityProcessor;

use Drupal\fut_activity\Plugin\ActivityProcessorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Entity\BaseEntityEvent;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  use StringTranslationTrait;


  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;



    /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, TimeInterface $date_time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
    $this->dateTime = $date_time;
  }

    /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('datetime.time')
    );
  }


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
  public function processActivity(BaseEntityEvent $event) {

    $dispatcher_type = $event->getDispatcherType();
    $entity = $event->getEntity();

    switch ($dispatcher_type) {
      case HookEventDispatcherInterface::ENTITY_INSERT:
        $keys = [
          'entity_type' => $entity->getEntityTypeId(),
          'entity_id' => (int)$entity->id(),
        ];
        $fields = [
          'activity' => $this->configuration['activity_creation'],
          'created' =>  $this->dateTime->getRequestTime(),
          'changed' => $this->dateTime->getRequestTime(),
        ];
        try {

          $existing_record = $this->connection->select('fut_activity','fa');
          $existing_record->addField('fa','activity_id');

          foreach ($keys as $key => $value) {
            $existing_record->condition($key,$value);
          }

          $existing_record->execute()->fetchCol();

          $teste="";

          $this->connection->merge('fut_activity')
            ->key($keys)
            ->fields($fields)
            ->expression('activity', 'activity + :inc', [':inc' => $this->configuration['activity_creation']])
            ->execute();
        } catch (\Throwable $th) {
          \Drupal::logger('system')->error($th->getMessage());
        }

        break;

        case HookEventDispatcherInterface::ENTITY_UPDATE:
        $fields = [
          'changed'=> $this->dateTime->getRequestTime(),
        ];
        try {
          $this->connection->update('fut_activity')
            ->fields($fields)
            ->condition('entity_id', $entity->id())
            ->condition('entity_type',$entity->getEntityTypeId())
            ->expression('activity', 'activity + :inc', [':inc' => $this->configuration['activity_update']])
            ->execute();
        } catch (\Throwable $th) {
          \Drupal::logger('system')->error($th->getMessage());
        }

        break;
    }



  }


}
