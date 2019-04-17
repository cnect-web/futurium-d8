<?php

namespace Drupal\fut_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Cache\Context\GroupCacheContext;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Discover page controller.
 */
class GroupPageController extends ControllerBase {

  /**
   * The current group.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $currentGroup;

  /**
   * The current group decorator.
   *
   * @var \Drupal\ngf_group\Entity\Decorator\NGFGroup
   */
  protected $gd;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    GroupCacheContext $group
  ) {
    $this->currentGroup = $group->getBestCandidate();
    $this->gd = new NGFGroup($this->currentGroup);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache_context.group')
    );
  }

  public function manageNavigation($group) {
    $user = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load($this->currentUser()->id());

    return $this->entityFormBuilder()->getForm($group, 'ngf_interests');
  }


}
