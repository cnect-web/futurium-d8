<?php
namespace Drupal\fut_group\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupContent;

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
      !empty($parameters['node']) && !empty($parameters['node'])
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
      $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', [
        'group' => $group->id()
      ]));
    }

    // Get the node for the current page
    $node = $route_match->getParameter('node');
    if (!empty($node)) {
      $groups = GroupContent::loadByEntity($node);
      if (!empty($groups)) {
        $group = reset($groups);
        $breadcrumb->addLink(Link::createFromRoute($group->label(), 'entity.group.canonical', [
          'group' => $group->id()
        ]));
      }

      $breadcrumb->addLink(Link::createFromRoute($node->getTitle(), 'entity.node.canonical', [
        'node' => $node->id()
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

}