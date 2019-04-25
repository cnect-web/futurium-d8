<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

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
    return \Drupal::service('entity.form_builder')->getForm($group, 'fut_navigation');
  }

}
