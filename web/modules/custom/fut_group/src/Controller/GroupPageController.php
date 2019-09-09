<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
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
   * Display view "fut_group_posts_management".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupPosts(Group $group) {
    return $this->getViewArray('fut_group_posts_management', [
      $group->id(),
    ]);
  }

  /**
   * Display view "fut_group_events_management".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupEvents(Group $group) {
    return $this->getViewArray('fut_group_events_management', [
      $group->id(),
    ]);
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
    return $this->getViewArray('fut_group_library', [
      $group->id(),
    ]);
  }

  /**
   * Display view "fut_collections".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupCollections(Group $group) {
    $collections_list_title = $this->t('Collection List');

    return [
      'collection_list' => [
        '#markup' => '<h3 class="collection-list-title">' . $collections_list_title . '</h3>',
        '#type' => 'view',
        '#title' => 'test',
        '#name' => 'fut_collections',
        '#display_id' => 'default',
        '#arguments' => [
          $group->id(),
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
    return $this->getViewArray('subgroups', [
      $group->id(),
    ]);
  }

  /**
   * Display view "fut_group_comments".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupComments(Group $group) {
    return $this->getViewArray('fut_group_comments', [
      $group->id(),
    ]);
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
    return $this->getViewArray('group_members', [
      $group->id(),
    ]);
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
    return $this->getViewArray('group_pending_members', [
      $group->id(),
    ]);
  }

  /**
   * Title callback for group add content pages.
   */
  public function addContentPageTitle(GroupInterface $group, $plugin_id) {
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);

    $nt = $this->entityTypeManager()
      ->getStorage('node_type')
      ->load($plugin->getEntityBundle());

    return $this->t("@group_name: New @content_type", [
      '@content_type' => $nt->label(),
      '@group_name' => $group->label(),
    ]);
  }

  /**
   * Display view "group_invitations".
   *
   * @param Drupal\group\Entity\Group $group
   *   The current group.
   *
   * @return array
   *   The renderable array.
   */
  public function groupInvitations(Group $group) {
    return $this->getViewArray('group_invitations', [
      $group->id(),
    ]);
  }

  /**
   * Prepares view renderable array.
   *
   * @param string $name
   *   The view machine name.
   * @param array $arguments
   *   Arguments to view aka Contextual filters.
   *
   */
  private function getViewArray($name, array $arguments = []) {
    return [
      'view' => [
        '#type' => 'view',
        '#name' => $name,
        '#display_id' => 'default',
        '#arguments' => $arguments,
      ],
    ];
  }

  /**
   * Manages layout settings of the group.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function manageLayout(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'fut_layout');
  }

  /**
   * Manages group privacy.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group item.
   *
   * @return mixed
   *   Form.
   */
  public function privacy(Group $group) {
    return $this->entityFormBuilder()->getForm($group, 'fut_layout');
  }

}
