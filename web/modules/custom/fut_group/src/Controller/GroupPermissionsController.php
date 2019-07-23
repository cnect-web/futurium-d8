<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Group permissions controller.
 */
class GroupPermissionsController extends ControllerBase {

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

    // Get all collections from all groups and prepare permissions for them.
    $groups = $this->entityTypeManager()->getStorage('group')->loadMultiple();
    foreach ($groups as $group) {
      // Get collections for the group.
      $collection_ids = \Drupal::entityQuery('taxonomy_term')
        ->condition('fut_related_group', $group->id())
        ->execute();

      foreach ($collection_ids as $collection_id) {
        $collection = Term::load($collection_id);
        $operations = [
          'view' => 'View',
          'update' => 'Edit',
          'delete' => 'Delete',
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
   *   Permissions
   * @param string $section
   *   Permission section.
   * @param string $title
   *   Permission title.
   * @param array $title_arguments
   *   Title arguments.
   * @param string $operation
   *   Operation to be performed
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
