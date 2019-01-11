<?php

namespace Drupal\ngf_group\Routing;

use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\node\Entity\NodeType;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class NGFGroupRouteSubscriber extends RouteSubscriberBase {

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
    if ($route = $collection->get('entity.group_content.create_form')) {
      $route->setDefault('_title_callback', '\Drupal\ngf_group\Routing\NGFGroupRouteSubscriber::gcCreateFormTitle');
    }
    if ($route = $collection->get('entity.group.canonical')) {
      $route->setDefault('_controller', '\Drupal\ngf_group\Controller\GroupPageController::landingPage');
    }
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setDefault('_controller', '\Drupal\ngf_user_profile\Controller\UserProfilePageController::editUserProfile');
    }
    // Change path to prevent breadcrumbs from picking up routes.
    if ($route = $collection->get('entity.group_content.collection')) {
      $route->setPath('/group/{group}/relations');
    }

  }

  /**
   * Callback for the group content creation form title.
   *
   * @Param GroupInterface $group     A Group entity.
   * @Param string         $plugin_id The plugin id.
   *
   * @return string
   *   A string to use as the title.
   */
  public function gcCreateFormTitle(GroupInterface $group, $plugin_id) {
    $plugin = $group->getGroupType()->getContentPlugin($plugin_id);

    if ($plugin->getEntityTypeId() == 'node') {
      $entity_type = NodeType::load($plugin->getEntityBundle());
    }
    else if ($plugin->getEntityTypeId() == 'group') {
      $entity_type = GroupType::load($plugin->getEntityBundle());
    }

    return t('Add @name in @group', [
      '@name' => !empty($entity_type) ?  $entity_type->label() : '',
      '@group' => $group->label(),
    ]);
  }

}
