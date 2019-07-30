<?php

namespace Drupal\fut_group\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Database\Connection;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "featured_group_featured_content",
 *   label = @Translation("Featured content"),
 *   bundles = {
 *     "group.*",
 *   }
 * )
 */
class FeaturedGroupFeaturedContent extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a FeaturedGroupFeaturedContent object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content plugin manager.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, GroupContentEnablerManagerInterface $plugin_manager, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
    $this->database = $database;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('database')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {
    $featured_content_ids = $this->getFeaturedContentIds();
    if (!empty($featured_content_ids)) {
      return $this->getFeaturedTeasers($featured_content_ids);
    }
  }

  /**
   * Gets Group Enabled content types.
   *
   * @return array
   *   Associative array with ctype => group_content_type.
   */
  private function getGroupCtypes() {
    // Get content plugins for the group's type.
    $plugin_ids = $this->pluginManager->getInstalledIds($this->entity->getGroupType());
    foreach ($plugin_ids as $key => $plugin_id) {
      if (strpos($plugin_id, 'group_node:') !== 0) {
        unset($plugin_ids[$key]);
      }
    }

    // Retrieve all of group content types.
    $storage = $this->entityTypeManager->getStorage('group_content_type');
    $properties = ['group_type' => $this->entity->bundle(), 'content_plugin' => $plugin_ids];
    $bundles = [];
    foreach ($storage->loadByProperties($properties) as $bundle => $group_content_type) {
      $ctype = explode('group_node:', $group_content_type->get('content_plugin'));
      $bundles[end($ctype)] = $bundle;
    }
    return $bundles;
  }

  /**
   * Gets Group featured event.
   *
   * @param string $gctype
   *   The group_content_type (group_content bundle).
   *
   * @return mixed
   *   We either return an array with Event ID or FALSE.
   */
  private function getFeaturedEvent($gctype) {
    $now = new DrupalDateTime('now');
    $query_events = $this->database->select('group_content_field_data', 'gc');

    $query_events->leftJoin('node__fut_event_date', 'date', 'gc.entity_id = date.entity_id');
    $query_events->leftJoin('node_field_data', 'nfd', 'gc.entity_id = nfd.nid');

    $query_events->fields('gc', ['entity_id'])
      ->condition('gc.type', $gctype, 'IN')
      ->condition('gc.gid', $this->entity->id())
      ->condition('nfd.status', 1)
      ->condition('date.fut_event_date_end_value', $now->format(DATETIME_DATETIME_STORAGE_FORMAT), '>=')
      ->orderBy('date.fut_event_date_end_value')
      ->range(0, 1);

    $events_ids = $query_events->execute()->fetchCol();

    return empty($events_ids) ? FALSE : $events_ids;
  }

  /**
   * Gets Group featured Posts.
   *
   * @param string $gctype
   *   The group_content_type (group_content bundle).
   * @param mixed $has_event
   *   This ditermines how many posts we should get.
   *   1 or 2 (if there isn't events we try to get 2 posts).
   *
   * @return mixed
   *   We either return an array with Event ID or FALSE.
   */
  private function getFeaturedPost($gctype, $has_event) {
    // NOTE: later we need to pick the most popular.
    $query_posts = $this->database->select('group_content_field_data', 'gc');
    $query_posts->leftJoin('node_field_data', 'nfd', 'gc.entity_id = nfd.nid');
    $query_posts->fields('gc', ['entity_id'])
      ->condition('gc.type', $gctype)
      ->condition('gc.gid', $this->entity->id())
      ->condition('nfd.status', 1);

    // Here if we don't have events we should get 2 posts.
    if ($has_event) {
      $query_posts->range(0, 1);
    }
    else {
      $query_posts->range(0, 2);
    }

    $post_ids = $query_posts->execute()->fetchCol();
    return $post_ids;
  }

  /**
   * Gets all featured content ids.
   *
   * @return array
   *   Array with ids of content to render.
   */
  private function getFeaturedContentIds() {
    $events_ids = [];
    $post_ids = [];
    foreach ($this->getGroupCtypes() as $ctype => $gctype) {
      // Get events.
      if ($ctype == 'fut_event') {
        $events_ids = $this->getFeaturedEvent($gctype);
      }
      // Get posts.
      if ($ctype == 'fut_post') {
        $post_ids = $this->getFeaturedPost($gctype, $events_ids);
        if ($events_ids === FALSE) {
          $events_ids = [];
        }
      }
    }
    $featured_content_ids = array_merge($events_ids, $post_ids);
    return $featured_content_ids;
  }

  /**
   * Prepares rendarable array to show featured content.
   *
   * @param array $featured_content_ids
   *   Array with ids of content to render.
   *
   * @return array
   *   Renderable array with nodes on teaser.
   */
  private function getFeaturedTeasers(array $featured_content_ids) {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $featured_group_content = $node_storage->loadMultiple($featured_content_ids);
    $view_builder = $this->entityTypeManager->getViewBuilder('node');
    $teasers = $view_builder->viewMultiple($featured_group_content, 'fut_teaser_lite');
    return $teasers;
  }

}
