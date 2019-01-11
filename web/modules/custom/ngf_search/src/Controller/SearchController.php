<?php

namespace Drupal\ngf_search\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\AccountInterface;
use Drupal\ngf_user_profile\Manager\UserFeedManager;
use Drupal\ngf_user_profile\Manager\UserManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;

/**
 * Profile page controller.
 */
class SearchController extends ControllerBase {

  /**
   * The user feed manager service.
   *
   * @var Drupal\ngf_user_profile\Manager\userFeedManager
   */
  protected $userFeedManager;

  /**
   * User manager.
   *
   * @var \Drupal\ngf_user_profile\Manager\UserManager
   */
  protected $userManager;

  /**
   * User instance.
   *
   * @var Drupal\user\Entity\User
   */
  protected $currentUserAccount = NULL;

  public function __construct(
    UserFeedManager $user_feed_manager,
    UserManager $userManager
  ) {
    $this->userFeedManager = $user_feed_manager;
    $this->userManager = $userManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ngf_user_profile.user_feed_manager'),
      $container->get('ngf_user_profile.user_manager')
    );
  }

  public function users() {
    $view = Views::getView('ngf_users_search');
    $view->setDisplay('user_search');
    $view->preExecute();
    $view->execute();

    $results = $view->result;
    $view->result = [];
    $search_block_content = $view->render();
    var_dump($view->result);

    return [
      'search_form' => $search_block_content,
      'user_list_form' => $this->formBuilder()->getForm('\Drupal\ngf_search\Form\AddToListForm', $results),
    ];
  }

}
