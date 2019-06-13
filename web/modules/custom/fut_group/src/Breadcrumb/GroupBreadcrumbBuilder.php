<?php

namespace Drupal\fut_group\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides a custom Grouop breadcrumb builder.
 */
class GroupBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {

    $parameters = $attributes->getParameters()->all();
    $views = [
      'fut_group_events',
      'fut_group_posts',
      'fut_group_library',
    ];
    if (
      !empty($parameters['view_id']) && in_array($parameters['view_id'], $views)  ||
      !empty($parameters['node']) && !empty($parameters['node']) ||
      !empty($parameters['group_content']) && !empty($parameters['group_content'])
    ) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();

    // Add a link to the homepage as our first crumb.
    $breadcrumb->addLink(Link::createFromRoute('Home', '<front>'));

    $group = $route_match->getParameter('group');
    if (!empty($group)) {

      // Add parent group if threre is one.
      $this->addParentGroupBreadcrumb($breadcrumb, $group);

      $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', [
        'group' => $group->id(),
      ]));
    }

    $group_content = $route_match->getParameter('group_content');
    if (!empty($group_content)) {
      $breadcrumb->addLink(Link::createFromRoute($group_content->getEntity()->label(), "entity.{$group_content->getContentPlugin()->getEntityTypeId()}.canonical", [
        $group_content->getContentPlugin()->getEntityTypeId() => $group_content->getEntity()->id(),
      ]));
    }

    $node = $route_match->getParameter('node');
    if (!empty($node)) {
      $group_content_items = GroupContent::loadByEntity($node);
      if (!empty($group_content_items)) {
        $group_content = reset($group_content_items);

        // Add parent group if threre is one.
        $this->addParentGroupBreadcrumb($breadcrumb, $group_content->getGroup());

        $breadcrumb->addLink(Link::createFromRoute($group_content->getGroup()->label(), 'entity.group.canonical', [
          'group' => $group_content->getGroup()->id(),
        ]));
      }

      $breadcrumb->addLink(Link::createFromRoute($node->getTitle(), 'entity.node.canonical', [
        'node' => $node->id(),
      ]));
    }

    $view = $route_match->getParameter('view_id');
    if (!empty($view)) {
      switch ($view) {
        case 'fut_group_events':
          $breadcrumb->addLink(Link::createFromRoute($this->t('Events'), '<nolink>'));
          break;

        case 'fut_group_posts':
          $breadcrumb->addLink(Link::createFromRoute($this->t('Posts'), '<nolink>'));
          break;

        case 'fut_group_library':
          $breadcrumb->addLink(Link::createFromRoute($this->t('Library'), '<nolink>'));
          break;
      }

    }

    // Don't forget to add cache control by a route.
    // Otherwise all pages will have the same breadcrumb.
    $breadcrumb->addCacheContexts(['route']);

    // Return object of type breadcrumb.
    return $breadcrumb;
  }

  /**
   * Adds parent group to breadcrumb in case there is one.
   *
   * @param \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
   *   The breadcrumb object.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group that we check if has a parent.
   */
  protected function addParentGroupBreadcrumb(Breadcrumb $breadcrumb, GroupInterface $group) {
    // Check if is a subgroup and add parent to breadcrumb.
    $group_contents = GroupContent::loadByEntity($group);

    if ($group_contents) {
      $group_content = reset($group_contents);
      $parent_group = $group_content->getGroup();

      if (!empty($parent_group)) {
        $breadcrumb->addLink(Link::createFromRoute($parent_group->label(), 'entity.group.canonical', [
          'group' => $parent_group->id(),
        ]));
      }
    }
  }

}
