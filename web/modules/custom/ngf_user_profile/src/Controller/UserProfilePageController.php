<?php

namespace Drupal\ngf_user_profile\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\AccountInterface;
use Drupal\ngf_user_profile\Manager\UserFeedManager;
use Drupal\ngf_user_profile\Manager\UserManager;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    $user = $user ?? $this->getCurrentUserAccount();
    return $this->getContent($this->getView('ngf_user_followers', 'followers', $user->id()), $user);
  }

  /**
   * {@inheritdoc}
   */
  public function following(EntityInterface $user = NULL) {
    $user = $user ?? $this->getCurrentUserAccount();
    return $this->getContent($this->getView('ngf_user_followers', 'following', $user->id()), $user);
  }

  /**
   * {@inheritdoc}
   */
  public function savedContent() {
    return $this->getContent($this->getView('ngf_saved_content', 'saved_content', NULL));
  }

  public function contact(EntityInterface $user) {
    $message = $this->entityTypeManager()
      ->getStorage('contact_message')
      ->create([
        'contact_form' => 'personal',
        'recipient' => $user
          ->id(),
      ]);
    $form = $this
      ->entityFormBuilder()
      ->getForm($message);

    return $this->getContent($form, $user);
  }

  protected function getUserList($users, $no_items_text) {
    return $this->getEntityList('user', 'compact', $users, $no_items_text);
  }

  protected function getContentList($content_items, $no_items_text) {
    return $this->getEntityList('node', 'teaser', $content_items, $no_items_text);
  }

  protected function getEntityList($entity_type, $view_mode, $entities, $no_items_text) {
    $items = [];
    $entity_builder = $this->entityTypeManager()->getViewBuilder($entity_type);
    foreach ($entities as $entity) {
      $items[] = $entity_builder->view($entity, $view_mode);
    }
    return count($items) > 0  ? $items : $this->getRenderMarkup($no_items_text);
  }

  /**
   * {@inheritdoc}
   */
  public function generalSettings() {
    return $this->getContent($this->entityFormBuilder()->getForm($this->getCurrentUserAccount(), 'ngf_general_settings'));
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
    return $this->getContent($this->entityFormBuilder()->getForm($this->getCurrentUserAccount(), 'ngf_interests'));
  }

  /**
   * {@inheritdoc}
   */
  public function about(EntityInterface $user) {
    return $this->getContent($this->getUserDisplay($user, 'ngf_about'), $user);
  }

  public function feed() {
    return $this->getContent($this->userFeedManager->getContent());
  }

  public function editUserProfile($user){
    if ($this->currentUser()->id() == $user->id()) {
      return $this->redirect('ngf_user_profile.page.general_settings');
    }
    else {
      return $this->entityFormBuilder()->getForm($user, 'default');
    }
  }

  public function viewUserProfile($user) {
    // Redirect all to profile pages.
    if ($user->id() ==  $this->currentUser()->id()) {
      $url = Url::fromRoute('ngf_user_profile.page.profile');
    }
    else {
      $url = Url::fromRoute('ngf_user_profile.page.user_profile', ['user' => $user->id()]);
    }
    $response = new RedirectResponse($url->toString());
    $response->send();
  }
}
