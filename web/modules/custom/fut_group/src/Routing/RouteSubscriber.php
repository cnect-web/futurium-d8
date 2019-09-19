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

    if ($route = $collection->get('view.group_invitations.page_1')) {
      // Here we alter this route in order to send user to our custom path,
      // in order to keep our custom local task,
      // after bulk invite batch is complete.
      $invitations_route = $collection->get('fut_group.manage_group.member_invitations');
      $fut_custom_path = $invitations_route->getPath();
      $fut_custom_title = $invitations_route->getDefault('_title');

      $route->setPath($fut_custom_path);
      $route->setDefaults([
        '_title' => $fut_custom_title,
        '_controller' => '\Drupal\fut_group\Controller\GroupPageController::groupInvitations',
      ]);
    }

    if ($route = $collection->get('group_permissions.override_group_permissions')) {
      // Put the group permissions form under 'Manage'.
      $route->setPath("/group/{group}/manage/group/permissions");
    }

    if ($route = $collection->get('view.fut_my_contributions.page_my_contributions')) {
      // Allow users to see only their own contributions.
      $route->setRequirement('_custom_access', '\Drupal\fut_group\Access\UserProfileAccessCheck::access');
    }

    if ($route = $collection->get('view.my_invitations.page_1')) {
      // Add custom access handler to only allow users to see their own invites.
      $route->setRequirement('_custom_access', '\Drupal\fut_group\Access\UserProfileAccessCheck::access');
    }

  }

}
