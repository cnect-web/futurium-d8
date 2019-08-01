<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function manageNavigation(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'fut_navigation');
  }

  /**
   * Show group about view_mode.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   Renderable array.
   */
  public function about(Group $group) {
    $view_builder = $this->entityTypeManager()->getViewBuilder('group');
    return $view_builder->view($group, 'fut_about');
  }

  /**
   * Gets title for group about page.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   The label of the group.
   */
  public function aboutTitle(Group $group) {
    return $group->label();
  }

  /**
   * Get collection form related to group by default.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function addCollection(Group $group) {
    $taxonomy_term = Term::create([
      'fut_related_group' => $group->id(),
      'vid' => 'fut_collections',
    ]);
    return $this->entityFormBuilder()->getForm($taxonomy_term, 'default');
  }

  /**
   * Get existing collection form.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Collection item.
   *
   * @return mixed
   *   Form.
   */
  public function editCollection(Group $group, Term $term) {
    return $this->entityFormBuilder()->getForm($term, 'default');
  }

  /**
   * Get collection delete form.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   * @param \Drupal\taxonomy\Entity\Term $term
   *   Collection item.
   *
   * @return mixed
   *   Form.
   */
  public function deleteCollection(Group $group, Term $term) {
    return $this->entityFormBuilder()->getForm($term, 'delete');
  }

  /**
   * Display view "group_nodes".
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
   * Display view "fut_group_library".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupLibrary(Group $group) {
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
   * Display view "fut_collections".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   * @param mixed $collection
   *   Collection Term ID or false (routing default).
   * @return array
   *   The renderable array.
   */
  public function groupCollections(Group $group, $collection) {
    $collection_term = $this->entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'fut_collections', 'tid' => $collection]);
    $collection_term = reset($collection_term);

    $pots_collection_display = ($collection_term) ? 'block_contextual_collection' : 'block_no_collection';

    $collections_list_title = $this->t('Collection List');
    $posts_colletion_title =  ($collection_term) ? $this->t('@collection Posts',['@collection' => $collection_term->label()]) : $this->t('Posts with no collection');

    return [
      'collection_list' => [
        '#markup' => '<div class="collection-list-title">'. $collections_list_title .'</div>',
        '#type' => 'view',
        '#title' => 'test',
        '#name' => 'fut_collections',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],

      'posts_collection' => [
        '#markup' => '<div class="posts-collection-title">'. $posts_colletion_title .'</div>',
        '#type' => 'view',
        '#name' => 'fut_group_posts_collection',
        '#display_id' => $pots_collection_display,
        '#arguments' => [
          $group->id(),
          $collection
        ],
      ],


    ];
  }

  /**
   * Display view "subgroups".
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

  /**
   * Display group overview.
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupOverview(Group $group) {
    return [
      '#markup' => 'This overview page will get useful info for group owners.',
    ];
  }

  /**
   * Display group edit.
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array of processed form for the given group.
   */
  public function groupEdit(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'edit');
  }

  /**
   * Display group navigation form.
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array of processed form for the given group.
   */
  public function groupNavigation(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'fut_navigation');
  }

  /**
   * Display view "group_members".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupMembers(Group $group) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'group_members',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

  /**
   * Display view "group_pending_members".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupRequests(Group $group) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => 'group_pending_members',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
        ],
      ],
    ];
  }

}
