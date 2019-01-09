<?php

namespace Drupal\ngf_user_profile\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\AccountInterface;
use Drupal\ngf_user_profile\Manager\UserFeedManager;
use Drupal\ngf_user_profile\Manager\UserManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Profile page controller.
 */
class UserProfilePageController extends UserProfileControllerBase {

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

  protected function getCurrentUserAccount() {
    if (empty($this->currentUserAccount)) {
      $this->currentUserAccount = User::load($this->currentUser()->id());
    }
    return $this->currentUserAccount;
  }

  /**
   * {@inheritdoc}
   */
  public function publications(EntityInterface $user = NULL) {
    return $this->getViewContent('publications', $user);
  }

  /**
   * {@inheritdoc}
   */
  public function events(EntityInterface $user = NULL) {
    return $this->getViewContent('events', $user);
  }

  /**
   * {@inheritdoc}
   */
  public function groups(EntityInterface $user = NULL) {
    return $this->getViewContent('groups', $user);
  }

  /**
   * {@inheritdoc}
   */
  public function followers(EntityInterface $user = NULL) {
    $text = t('<p>There are no followers yet</p>');
    return $this->getContent($this->getUserList($this->userManager->getFollowersUsersList($user), $text), $user);
  }

  /**
   * {@inheritdoc}
   */
  public function following(EntityInterface $user = NULL) {
    $text = t("<p>There are no following users yet</p>");
    return $this->getContent($this->getUserList($this->userManager->getFollowingUsersList($user), $text), $user);
  }

  public function contact(EntityInterface $user) {
    $message = $this
      ->entityTypeManager()
      ->getStorage('contact_message')
      ->create(array(
        'contact_form' => 'personal',
        'recipient' => $user
          ->id(),
      ));
    $form = $this
      ->entityFormBuilder()
      ->getForm($message);

    return $this->getContent($form);
  }

  /**
   * {@inheritdoc}
   */
  public function generalSettings() {
    return $this->getContent($this->getEntityForm('default', $this->getCurrentUserAccount(), 'user'));
  }

  /**
   * {@inheritdoc}
   */
  public function privateSettings() {
    return $this->getContent($this->formBuilder()->getForm('Drupal\ngf_user_profile\Form\UserPrivateSettingsForm'));
  }

  /**
   * {@inheritdoc}
   */
  public function locationSettings() {
    return $this->getContent($this->formBuilder()->getForm('Drupal\ngf_user_profile\Form\UserLocationSettingsForm'));
  }

  /**
   * {@inheritdoc}
   */
  public function interestsSettings() {
    return $this->getContent($this->getEntityForm('ngf_interests', $this->getCurrentUserAccount(), 'user'));
  }

  /**
   * {@inheritdoc}
   */
  public function about(EntityInterface $user) {
    return $this->getContent($this->getUserDisplay($user, 'ngf_about'), $user);
  }

  public function feed() {

    $publications = $this->userFeedManager->getContent();
    $page = pager_find_page();
    $num_per_page = 10;
    $offset = $num_per_page * $page;
    $result = array_slice($publications, $offset, $num_per_page);

    // Now that we have the total number of results, initialize the pager.
    pager_default_initialize(count($publications), $num_per_page);

    // Create a render array with the search results.
    $render = [];
    if (count($result) > 0) {
      foreach ($result as $item) {
        $message = $this->entityTypeManager()->getViewBuilder('message')
          ->view($item, 'full');
        // There is a bug partial is still displayed even it's hidden in the view mode.
        unset($message['partial_0']);
        $render['content'][] = $message;
      }
      $render['content'][] = [
        '#type' => 'pager',
      ];
    }
    else {
      $render[] = $this->getRenderMarkup('<p>There are no items in your feed</p>');
    }
    return $this->getContent($render);
  }



}
