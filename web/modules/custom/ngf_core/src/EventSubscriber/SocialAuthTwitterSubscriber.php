<?php

namespace Drupal\ngf_core\EventSubscriber;

use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\Event\SocialAuthUserEvent;
use Drupal\social_auth\Event\SocialAuthEvents;
use Drupal\social_auth_twitter\TwitterAuthManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Reacts on Social Auth events.
 */
class SocialAuthTwitterSubscriber implements EventSubscriberInterface {

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
   * The Social Auth Twitter manager.
   *
   * @var \Drupal\social_auth_twitter\TwitterAuthManager
   */
  protected $twitterManager;

  /**
   * SocialAuthSubscriber constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used to retrieve the token from session.
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of the SDK used by the Social Auth implementer.
   * @param \Drupal\social_auth_twitter\TwitterAuthManager $twitterManager
   *   The Social Auth Twitter manager.
   */
  public function __construct(SessionInterface $session, NetworkManager $network_manager, TwitterAuthManager $twitterManager) {
    $this->session = $session;
    $this->networkManager = $network_manager;
    $this->twitterManager = $twitterManager;
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
    $plugin = $event->getPluginId();
    if ($plugin == 'social_auth_twitter') {
      // DOCS: https://developer.twitter.com/en/docs/accounts-and-users/manage-account-settings/api-reference/get-account-verify_credentials
      if ($this->session->get('social_auth_twitter_access_token')) {
        $access_token = $this->session->get('social_auth_twitter_access_token');

        $client = $this->networkManager
          ->createInstance('social_auth_twitter')
          ->getSdk2(
            $access_token['oauth_token'],
            $access_token['oauth_token_secret']
          );

        $content = $client->get("account/verify_credentials");

        $twitter['username'] = $content->screen_name;
        $twitter['name'] = $content->name;
        $twitter['bio'] = $content->description;
        $twitter['location'] = $content->location;

        // Use twitter username as username.
        $user = $event->getUser();
        $user->setUsername($twitter['username']);

        // @todo: make target fields configurable.
        // Use name for realname fields.
        $names = explode(' ', $twitter['name']);
        $total_names = count($names);
        if ($total_names) {
          $first_name = $names[0];
          $user->field_ngf_first_name->value = $first_name;
          if ($total_names > 1) {
            $last_name = $names[count($names) - 1];
            $user->field_ngf_last_name->value = $last_name;
          }
        }
        $user->field_ngf_biography->value = $twitter['bio'];
        $user->save();
      }
    }
  }

  /**
   * Sets a drupal message when a user logs in.
   *
   * @param \Drupal\social_auth\Event\SocialAuthUserEvent $event
   *   The Social Auth user event object.
   */
  public function onUserLogin(SocialAuthUserEvent $event) {
  }

}
