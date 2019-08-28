<?php

namespace Drupal\fut_group\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Session\AccountInterface;
use Drupal\ggroup\GroupHierarchyManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the node entity type.
 *
 * @EntityReferenceSelection(
 *   id = "fut_group:group_one_level",
 *   label = @Translation("Group selection"),
 *   entity_types = {"group"},
 *   group = "fut_group",
 *   weight = 1
 * )
 */
class OneLevelGroupReferenceSelection extends DefaultSelection {


  /**
   * The ggroup Hierachy manager service.
   *
   * @var \Drupal\ggroup\GroupHierarchyManagerInterface
   */
  protected $gHierachyManager;

  /**
   * Constructs a new DefaultSelection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\ggroup\GroupHierarchyManagerInterface $group_hierachy_manager
   *   The ggroup Hierachy manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, EntityFieldManagerInterface $entity_field_manager = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityRepositoryInterface $entity_repository = NULL, GroupHierarchyManagerInterface $group_hierachy_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
    $this->gHierachyManager = $group_hierachy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('ggroup.group_hierarchy_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->getConfiguration()['target_type'];

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return [];
    }

    $options = [];
    $entities = $this->entityTypeManager->getStorage($target_type)->loadMultiple($result);
    foreach ($entities as $entity_id => $entity) {
      if (empty($this->gHierachyManager->getGroupSubgroupIds($entity_id)) && empty($this->gHierachyManager->getGroupSupergroupIds($entity_id))) {
        $bundle = $entity->bundle();
        $options[$bundle][$entity_id] = Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
      }
    }

    return $options;
  }

}
