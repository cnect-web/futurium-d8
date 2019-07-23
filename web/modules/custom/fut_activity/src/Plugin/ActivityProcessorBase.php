<?php

namespace Drupal\fut_activity\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\fut_activity\ActivityRecordStorageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for Activity processor plugins.
 */
abstract class ActivityProcessorBase extends PluginBase implements ActivityProcessorInterface {

  use StringTranslationTrait;

  /**
   * The activity record storage service.
   *
   * @var \Drupal\fut_activity\ActivityRecordStorageInterface
   */
  protected $activityRecordStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActivityRecordStorageInterface $activity_record_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->activityRecordStorage = $activity_record_storage;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('fut_activity.activity_record_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processActivity(Event $event) {
    // code...
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
    ] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
    return $this;
  }

}
