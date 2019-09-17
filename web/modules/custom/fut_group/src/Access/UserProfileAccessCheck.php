<?php

namespace Drupal\fut_group\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom Access handler to check if user from route matches current user.
 */
class UserProfileAccessCheck implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * UserProfileAccessCheck constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * Check if user from route is current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {

    $current_user = $this->currentUser->getAccount();

    $route_parameter = $this->routeMatch->getParameter('user');

    if ($route_parameter instanceof UserInterface) {
      $user_from_route = $route_parameter;
    }
    else {
      $user_from_route = $this->entityTypeManager->getStorage('user')->load($route_parameter);
    }

    if ($user_from_route->id() == $current_user->id()) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::allowedIfHasPermission($current_user, 'administer users');
    }
    return $result;
  }

}
