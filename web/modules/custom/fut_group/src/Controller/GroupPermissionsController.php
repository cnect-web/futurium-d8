<?php

namespace Drupal\fut_group\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\fut_group\RequestEntityExtractor;

/**
 * Group permissions controller.
 */
class GroupPermissionsController extends ControllerBase {

  /**
   * Drupal\fut_group\RequestEntityExtractor definition.
   *
   * @var Drupal\fut_group\RequestEntityExtractor
   */
  protected $entityExtractor;

  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $entityQuery, RequestEntityExtractor $entityExtractor) {
    $this->entityQuery = $entityQuery;
    $this->entityExtractor = $entityExtractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('fut_group.request_entity_extractor')
    );
  }

  /**
   * Returns custom group permissions.
   *
   * @return array
   *   Permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function permissions() {
    $permissions = [];
    $permissions = array_merge($permissions, $this->getCollectionPermissions());
    return $permissions;
  }

  /**
   * Gets permissions for group collections.
   *
   * @return array
   *   Permissions array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getCollectionPermissions() {
    $permissions = [];

    $group = $this->entityExtractor->getGroup();
    if (!empty($group)) {

      // Get collections for the group.
      $collection_ids = $this->entityQuery->get('taxonomy_term')
        ->condition('fut_related_group', $group->id())
        ->execute();

      foreach ($collection_ids as $collection_id) {
        $collection = $this
          ->entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($collection_id);

        $operations = [
          'create' => 'Create nodes for ',
          'view' => 'View nodes for ',
          'update' => 'Edit nodes for ',
          'delete' => 'Delete nodes for ',
        ];
        foreach ($operations as $operation => $operation_name) {
          $title_arguments = [
            '%name' => $collection->getName(),
            '%action' => $operation_name,
          ];
          $this->getCollectionPermission($permissions,
            $collection->getName(),
            '%action %name collection',
            $title_arguments,
            $operation,
            $collection_id,
            $group->id()
          );
        }
      }
    }

    return $permissions;
  }

  /**
   * Get collection specific permission item.
   *
   * @param array $permissions
   *   Permissions.
   * @param string $section
   *   Permission section.
   * @param string $title
   *   Permission title.
   * @param array $title_arguments
   *   Title arguments.
   * @param string $operation
   *   Operation to be performed.
   * @param int $collection_id
   *   Collection id.
   * @param int $gid
   *   Group id.
   */
  protected function getCollectionPermission(array &$permissions, $section, $title, array $title_arguments, $operation, $collection_id, $gid) {
    $permissions["$operation $collection_id collection"] = [
      'title' => $title,
      'title_args' => $title_arguments,
      'gid' => $gid,
      'section' => $section,
    ];
  }

}
