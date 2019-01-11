<?php

namespace Drupal\ngf_core\EventSubscriber;

use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Drupal\social_auth\Event\SocialAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Reacts on Social Auth events.
 */
class SocialAuthSubscriber implements EventSubscriberInterface {

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
   * SocialAuthSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used to retrieve the token from session.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of the SDK used by the Social Auth implementer.
   * @param \Drupal\social_auth_google\GoogleAuthManager $google_manager
   *   The Social Auth Google manager.
   */
  public function __construct(SessionInterface $session, NetworkManager $network_manager) {
    $this->session = $session;
    $this->networkManager = $network_manager;
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
  }

  /**
   * Sets a drupal message when a user logs in.
   *
   * @param \Drupal\social_auth\Event\SocialAuthUserEvent $event
   *   The Social Auth user event object.
   */
  public function onUserLogin(SocialAuthUserEvent $event) {
  }

  public function l($var) {
    $ddumper = \Drupal::service('devel.dumper');
    $ddumper->debug($var);
  }

}

