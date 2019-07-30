<?php

namespace Drupal\fut_group\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\gnode\Plugin\GroupContentEnabler\GroupNode;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides dynamic local tasks to add nodes to group.
 */
class GroupAddContentLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The base plugin ID.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConfigTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct($base_plugin_id, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($base_plugin_id,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $group_types = $this->entityTypeManager->getStorage('group_type')->loadMultiple();
    $route_name = 'entity.group_content.create_form';

    foreach ($group_types as $group_type) {
      foreach ($group_type->getInstalledContentPlugins() as $plugin_id => $plugin) {
        $derivative = $this->basePluginId . $plugin->getEntityBundle();
        if ($plugin instanceof GroupNode) {
          $this->derivatives[$derivative] = $base_plugin_definition;
          $this->derivatives[$derivative]['group_content_enabler_plugin_id'] = $plugin_id;
          $this->derivatives[$derivative]['group_content_enabler_plugin_label'] = $this->entityTypeManager->getStorage('node_type')->load($plugin->getEntityBundle())->label();
          $this->derivatives[$derivative]['class'] = '\\Drupal\\fut_group\\Plugin\\Menu\\LocalAction\\GroupAddNode';
          $this->derivatives[$derivative]['route_name'] = $route_name;
        }
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}