<?php

namespace Drupal\fut_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    if ($route = $collection->get('entity.group_content.create_form')) {
      $route->setDefault('_title_callback', '\Drupal\fut_group\Controller\GroupPageController::addContentPageTitle');
    }

    if ($route = $collection->get('view.fut_group_posts.page_group_posts')) {
      // Allow the views to have an optional argument.
      $route->setDefault('collection', 'all');
    }

    if ($route = $collection->get('view.fut_group_events.page_group_events')) {
      // Allow the views to have an optional argument.
      $route->setDefault('collection', 'all');
    }

    if ($route = $collection->get('view.fut_group_library.page_group_library')) {
      // Allow the views to have an optional argument.
      $route->setDefault('collection', 'all');
    }

    if ($route = $collection->get('group_permissions.override_group_permissions')) {
      // Put the group permissions form under 'Manage'.
      $route->setPath("/group/{group}/manage/group/permissions");
    }
  }

}
