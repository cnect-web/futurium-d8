<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Drupal\comment\Entity\Comment;
use Drupal\flag\FlagService;
use Drupal\message\Entity\Message;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\ngf_user_profile\MessageTrait;

class NotificationManager {

  use FlagTrait;
  use MessageTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  /**
   * NotificationManager constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\flag\FlagService $flag
   *   The flag service.
   */
  public function __construct(
    MessengerInterface $messenger,
    FlagService $flag
  ) {
    $this->flag = $flag;
    $this->messenger = $messenger;
  }

  /**
   * Get list of users following the current user.
   *
   * @param User $user
   *   User.
   *
   * @return array
   *   List of following users.
   */
  public function getFollowers(User $user) {
    return $this->flag->getFlaggingUsers($user, $this->getFollowUserFlag());
  }

  /**
   * Notify following users about a new node.
   *
   * @param \Drupal\user\Entity\Node $node
   *   Created node.
   */
  public function notifyFollowersAboutNewContent(Node $node) {
    $followers = $this->getFollowers($node->getOwner());
    foreach ($followers as $follower) {
      $message = Message::create([
        'template' => 'ngf_new_post',
        'uid' => $follower->id(),
      ]);
      $message->set('field_ngf_created_content', $node);
      $message->set('field_ngf_author', $node->getOwner());
      $message->save();
    }
  }

  /**
   * Notify owner about a new node.
   *
   * @param \Drupal\comment\Entity\Comment $comment
   *  Created comment.
   */
  public function notifyOwnerAboutNewComment(Comment $comment) {
    $message = Message::create([
      'template' => 'ngf_post_new_comment',
      'uid' => $comment->getCommentedEntity()->getOwnerId(),
    ]);
    $message->set('field_ngf_created_comment', $comment);
    $message->set('field_ngf_author', $comment->getOwner());
    $message->save();
  }

  /**
   * Get list of current user notifications.
   *
   * @return \Drupal\message\MessageInterface[]
   *   List of notifications.
   */
  public function getUserNotifications() {
    $query = \Drupal::entityQuery('message');
    $query->condition('uid', \Drupal::currentUser()->id());
    $results = $query->execute();
    return Message::loadMultiple($results);
  }

  /**
   * Mark notification as read.
   *
   * @param int $message_id
   *   Message id.
   *
   */
  public function markNotificationAsRead($message_id) {
    $message = Message::load($message_id);
    if (!empty($message)) {
      if ($message->getOwnerId() == \Drupal::currentUser()->id()) {
        $message->set('field_ngf_is_read', 1);
        $message->save();
        $this->addMessage(t('Notification has been marked as read'));
      }
      else {
        $this->addError(t('This notification does not belong to you'));
      }
    }
    else {
      $this->addError(t('Message does not exist'));
    }
  }

  /**
   * Remove a notification by id.
   *
   * @param int $message_id
   *   Message id.
   */
  public function removeNotification($message_id) {
    $message = Message::load($message_id);
    if (!empty($message)) {
      if ($message->getOwnerId() == \Drupal::currentUser()->id()) {
        $message->delete();
        $this->addMessage(t('Notification has been removed'));
      }
      else {
        $this->addError(t('This notification does not belong to you'));
      }
    }
    else {
      $this->addError(t('Message does not exist'));
    }
  }


}
