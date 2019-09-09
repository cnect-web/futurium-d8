<?php

namespace Drupal\fut_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class GroupJoinRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class GroupJoinRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Get Join Group route and change the default controller.
    if ($route = $collection->get('entity.group.join')) {
      $route->setDefaults([
        '_controller' => '\Drupal\fut_group\Controller\NoFormGroupJoinController::join',
      ]);
    }
  }

}
