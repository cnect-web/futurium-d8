<?php

namespace Drupal\ngf_user_profile\Routing;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class NGFUserProfileRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -9999];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_title_callback', '\Drupal\ngf_user_profile\Routing\NGFUserProfileRouteSubscriber::loginTitle');
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setDefault('_controller', '\Drupal\ngf_user_profile\Controller\UserProfilePageController::editUserProfile');
    }
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_controller', '\Drupal\ngf_user_profile\Controller\UserProfilePageController::viewUserProfile');
    }
  }

  /**
   * Callback for the login page title.
   *
   * @Param GroupInterface $group     A Group entity.
   * @Param string         $plugin_id The plugin id.
   *
   * @return string
   *   A string to use as the title.
   */
  public function loginTitle() {
    return t('Sign In');
  }

}
