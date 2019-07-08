<?php

namespace Drupal\fut_activity;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class ActivityProcessor.
 */
class ActivityProcessor implements ActivityProcessorInterface {

  /**
   * Drupal\Core\Queue\QueueFactory definition.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Constructs a new ActivityProcessor object.
   */
  public function __construct(QueueFactory $queue, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, Connection $connection, TimeInterface $date_time) {
    $this->queue = $queue;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->dateTime = $date_time;

  }

  public function getTrackerConfig(ContentEntityInterface $entity) {
    $entity_bundle = $entity->bundle();
    $config_id = $this->entityTypeManager->getStorage('entity_activity_tracker')->getQuery()
      ->condition('entity_bundle',$entity_bundle)
      ->execute();

    $config_id = reset($config_id);

    $traker_config = $this->configFactory->get('fut_activity.entity_activity_tracker.'.$config_id);

    return $traker_config;

  }

  public function incrementActivityValue(ImmutableConfig $tracker_config, ContentEntityInterface $entity, string $op, int $value = 0) {

    //NEED TO PUT THESE OPERATIONS IN CONST

    switch ($op) {
      case 'create':
        $fields = [
          'entity_type' => $tracker_config->get('entity_type'),
          'entity_id' => $entity->id(),
          'activity' => $tracker_config->get('activity_creation'),
          'created' => $this->dateTime->getRequestTime(),
          'changed' => $this->dateTime->getRequestTime(),
        ];
        try {
          $this->connection->insert('fut_activity')
            ->fields($fields)
            ->execute();
        } catch (\Throwable $th) {
          \Drupal::logger('system')->error($th->getMessage());
        }

        break;

      case 'update':
        $fields = [
          // 'entity_type' => $tracker_config->get('entity_type'),
          // 'entity_id' => $entity->id(),
          'changed'=> $this->dateTime->getRequestTime(),
        ];
        try {
          $this->connection->update('fut_activity')
            ->fields($fields)
            ->condition('entity_id', $entity->id())
            ->condition('entity_type',$entity->getEntityTypeId())
            ->expression('activity', 'activity + :inc', [':inc' => $tracker_config->get('activity_update')])
            ->execute();
        } catch (\Throwable $th) {
          \Drupal::logger('system')->error($th->getMessage());
        }

        break;

      case 'force_value':
        // here we will set a specific activity value to the record.
        break;

      case 'force_update':
        // here we will increment a specific activity value to the record.
        break;


      default:
        # code...
        break;
    }

  }

  public function deleteActivityRecord(ContentEntityInterface $entity) {
    try {
      $this->connection->delete('fut_activity')
        ->condition('entity_id', $entity->id(), '=')
        ->execute();
    } catch (\Throwable $th) {
      \Drupal::logger('system')->error($th->getMessage());
    }
    

  }


}
