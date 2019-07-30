<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;

/**
 * Discover page controller.
 */
class GroupPageController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_context.group')
    );
  }

  /**
   * Manages navigation of the group.
   *
   * @param Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function manageNavigation(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'fut_navigation');
  }

  /**
   * Manages navigation of the group.
   *
   * @param Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function about(Group $group) {
    $view_builder = $this->entityTypeManager()->getViewBuilder('group');
    return $view_builder->view($group, 'fut_about');
  }

  public function aboutTitle(Group $group) {
    return $group->label();
  }

  public function collections(Group $group) {
    $view_builder = $this->entityTypeManager()->getViewBuilder('group');
    return $view_builder->view($group, 'fut_about');
  }

  public function addCollection(Group $group) {
    $taxonomy_term = Term::create([
      'fut_related_group' => $group->id(),
      'vid' => 'fut_collections',
    ]);
    return $this->entityFormBuilder()->getForm($taxonomy_term, 'default');
  }

  public function editCollection(Group $group, Term $term) {
    return $this->entityFormBuilder()->getForm($term, 'default');
  }

  public function deleteCollection(Group $group, Term $term) {
    return $this->entityFormBuilder()->getForm($term, 'delete');
  }

  /**
   * Display view "group_nodes"
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupContent(Group $group) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'group_nodes',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

  /**
   * Display view "fut_group_library"
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupLibrary(Group $group){
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'fut_group_library',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

  /**
   * Display view "fut_collections"
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupCollections(Group $group) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'fut_collections',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

  /**
   * Display view "subgroups"
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupSubgroups(Group $group) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'subgroups',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

}
