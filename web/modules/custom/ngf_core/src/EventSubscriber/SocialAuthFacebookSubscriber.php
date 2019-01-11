<?php

namespace Drupal\ngf_core\EventSubscriber;

use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth_facebook\FacebookAuthManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Reacts on Social Auth events.
 */
class SocialAuthFacebookSubscriber implements EventSubscriberInterface {

  /**
   * Used to retrieve the token from session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used to get an instance of the SDK used by the Social Auth implementer.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  protected $networkManager;

  /**
   * The Social Auth Facebook manager.
   *
   * @var \Drupal\social_auth_facebook\FacebookAuthManager
   */
  protected $facebookManager;

  /**
   * SocialAuthSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used to retrieve the token from session.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of the SDK used by the Social Auth implementer.
   * @param \Drupal\social_auth_facebook\FacebookAuthManager $facebookManager
   *   The Social Auth Facebook manager.
   */
  public function __construct(SessionInterface $session, NetworkManager $network_manager, FacebookAuthManager $facebookManager) {
    $this->session = $session;
    $this->networkManager = $network_manager;
    $this->facebookManager = $facebookManager;
  }

  /**
   * {@inheritdoc}
   *
   * Returns an array of event names this subscriber wants to listen to.
   * For this case, we are going to subscribe for user creation and login
   * events and call the methods to react on these events.
   */
  public static function getSubscribedEvents() {
    $events[SocialAuthEvents::USER_CREATED] = ['onUserCreated'];
    $events[SocialAuthEvents::USER_LOGIN] = ['onUserLogin'];

    return $events;
  }

  /**
   * Alters the user name.
   *
   * @param \Drupal\social_auth\Event\SocialAuthUserEvent $event
   *   The Social Auth user event object.
   */
  public function onUserCreated(SocialAuthUserEvent $event) {
    if ($event->getPluginId() == 'social_auth_facebook') {
      $fbProfile = $this->facebookManager->getUserInfo();
      $user = $event->getUser();
      $user->setUsername($fbProfile->getName());
      $user->field_ngf_first_name->value = $fbProfile->getFirstName();
      $user->field_ngf_last_name->value = $fbProfile->getLastName();
      $user->field_ngf_biography->value = $fbProfile->getBio();
      $user->save();
    }
  }

  /**
   * Sets a drupal message when a user logs in.
   *
   * @param \Drupal\social_auth\Event\SocialAuthUserEvent $event
   *   The Social Auth user event object.
   */
  public function onUserLogin(SocialAuthUserEvent $event) {
    // If user logs in using social_auth_google and the access token exists.
    if ($event->getPluginId() == 'social_auth_facebook') {
    }
  }

}
