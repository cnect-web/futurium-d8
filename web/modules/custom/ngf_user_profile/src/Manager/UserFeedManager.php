<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\comment\Entity\Comment;
use Drupal\flag\FlagService;
use Drupal\comment\CommentStatistics;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\message\Entity\Message;
use Drupal\ngf_user_profile\UserFeedAction;
use Drupal\ngf_user_profile\UserFeedHelper;

class UserFeedManager {

  use FlagTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagService
   */
  protected $flag;

  protected $interests = [];
  protected $currentUserAccount = NULL;

  public function __construct(AccountInterface $current_user, FlagService $flag) {
    $this->currentUser = $current_user;
    $this->flag = $flag;
  }

  protected function getCurrentUserAccount() {
    if (empty($this->currentUserAccount)) {
      $this->currentUserAccount = User::load($this->currentUser->id());
    }
    return $this->currentUserAccount;
  }

  protected function getUserInterests() {
    if (empty($this->interests)) {
      $interests = [];
      $user = $this->getCurrentUserAccount();
      if (!empty($user)) {
        $interests = $user->get('field_ngf_interests');
      }
      foreach ($interests as $interest) {
        $value = $interest->getValue();
        if (!empty($value)) {
          $this->interests[$value['target_id']] = $value['target_id'];
        }
      }
    }
    return $this->interests;
  }

  protected function createUserFeedItem($users, $following_entity, $action, $created_entity) {
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_user_feed_item'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_following_entity', $following_entity);
      $message->set('field_ngf_user_feed_action', $action);
      $message->set('field_ngf_created_entity', $created_entity);
      $message->save();
    }
  }

  public function createCommentUserFeed(Comment $comment) {
    /**
     * Create a User feed item for users who follow the commented content
     * and the owner of the content, if the author of the comment is not the
     * author of the content.
     */
    $this->createUserFeedFollowingContentNewComment($comment);
    $this->createUserFeedOwnContentNewComment($comment);
    $this->createUserFeedFollowingUserNewComment($comment);
  }

  protected function createUserFeedFollowingContentNewComment(Comment $comment) {
    // Create a user feed for users who follow the commented content.
    $users = $this->removeAuthor($this->getUsersFollowingContent($comment->getCommentedEntity()), $comment->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_content_comment'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_comment', $comment);
      $message->set('field_ngf_following_content', $comment->getCommentedEntity());
      $message->save();
    }

//    $this->createUserFeedItem(
//      $this->removeAuthor($this->getUsersFollowingContent($comment->getCommentedEntity()), $comment->getOwner()),
//      $comment->getCommentedEntity(),
//      UserFeedAction::following_content_comment,
//      $comment
//    );
  }

  protected function createUserFeedOwnContentNewComment(Comment $comment) {
    // If author of the comment and author of the content are not the same.
    $users = [$comment->getCommentedEntity()->getOwner()];
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_content_comment'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_comment', $comment);
      $message->set('field_ngf_following_content', $comment->getCommentedEntity());
      $message->save();
    }
  }

  protected function createUserFeedFollowingUserNewComment(Comment $comment) {
    // Create a user feed for the following user who commented something.
    $users = $this->removeAuthor($this->getUsersFollowingUser($comment->getOwner()), $comment->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_user_comment'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_comment', $comment);
      $message->set('field_ngf_following_user', $comment->getOwner());
      $message->set('field_ngf_commented_content', $comment->getCommentedEntity());
      $message->save();
    }
  }

  public function createContentUserFeed(Node $node) {
    $this->createUserFeedFollowingUserNewContent($node);
  }

  protected function createUserFeedFollowingUserNewContent(Node $node) {
    // Create a user feed for users who follow the user who has created content.
    $users = $this->removeAuthor($this->getUsersFollowingUser($node->getOwner()), $node->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_user_content'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_content', $node);
      $message->set('field_ngf_following_user', $node->getOwner());
      $message->save();
    }
  }

  public function createGroupContentUserFeed(GroupContent $group_content) {
    /**
     * Create a User feed item for users who follow the group where content is
     * and users which have the same interests.
     */
    $this->createUserFeedFollowingGroupNewContent($group_content);
    $this->createUserFeedMemberGroupNewContent($group_content);
  }

  protected function createUserFeedFollowingGroupNewContent(GroupContent $group_content) {
    // Create a user feed for users who follow the group where content
    // was created.
    $users = $this->removeAuthor($this->getUsersFollowingGroup($group_content->getGroup()), $group_content->getEntity()->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_group_content'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_content', $group_content->getEntity());
      $message->set('field_ngf_following_group', $group_content->getGroup());
      $message->save();
    }
  }

  protected function createUserFeedMemberGroupNewContent(GroupContent $group_content) {
    // Create a user feed for members of the group where content
    // was created.
    $users = $this->removeAuthor($group_content->getGroup()->getContentEntities('group_membership'), $group_content->getEntity()->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_member_group_content'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_content', $group_content->getEntity());
      $message->set('field_ngf_following_group', $group_content->getGroup());
      $message->save();
    }
  }

  protected function getUsersFollowingContent(Node $node) {
    return $this->flag->getFlaggingUsers($node, $this->getFollowContentFlag());
  }

  protected function getUsersFollowingGroup(Group $group) {
    return $this->flag->getFlaggingUsers($group, $this->getFollowGroupFlag());
  }

  protected function getUsersFollowingUser(User $user) {
    return $this->flag->getFlaggingUsers($user, $this->getFollowUserFlag());
  }

  protected function getUsersByInterests(array $interests = []) {
    $users = [];
    if (!empty($interests)) {
      $query = \Drupal::entityQuery('user');
      $query->condition('field_ngf_interests', $interests, 'IN');
      $users = User::loadMultiple($query->execute());
    }
    return $users;
  }

  protected function getValuesArrayFromTargets($interests) {
    $output = [];
    foreach ($interests as $interest) {
      $value = $interest->getValue();
      if (!empty($value)) {
        $output[$value['target_id']] = $value['target_id'];
      }
    }
    return $output;
  }

  protected function filterUsers(array $users = []) {
    $users = array_unique($users, SORT_REGULAR);
    // Remove the current user.
    if (($key = array_search($this->getCurrentUserAccount(), $users)) !== false) {
      unset($users[$key]);
    }
    return $users;
  }

  protected function removeAuthor(array $users = [], $author) {
    // Remove user entity.
    if (($key = array_search($author, $users)) !== false) {
      unset($users[$key]);
    }
    return $users;
  }

  public function createGroupUserFeed(Group $group) {
    // Create a user feed for members of the group where content
    // was created.
    $users = $this->removeAuthor($this->getUsersFollowingUser($group->getOwner()), $group->getOwner());
    if (!empty($users)) {
      $message = Message::create([
        'template' => 'ngf_uf_following_user_group'
      ]);
      $message->set('field_ngf_users', $users);
      $message->set('field_ngf_created_group', $group);
      $message->set('field_ngf_following_user', $group->getOwner());
      $message->save();
    }
  }

  public function getContent() {
    $query = \Drupal::entityQuery('message');
    $query->condition('template', [
      'ngf_uf_following_content_comment',
      'ngf_uf_own_content_comment',
      'ngf_uf_following_user_comment',
      'ngf_uf_following_user_content',
      'ngf_uf_following_user_group',
      'ngf_uf_following_group_content',
      'ngf_uf_member_group_content',
    ], 'IN');
    $query->condition('field_ngf_users', [$this->currentUser->id()] , 'IN');
    $query->sort('created', 'DESC');
    return $this->filterUniqueUserFeeds(Message::loadMultiple($query->execute()));
  }

  public function filterUniqueUserFeeds($items) {
    $stored_items = [];
    foreach ($items as $key => $item) {
      $template_id = $item->getTemplate()->id();

      // Content created.
      if (in_array($template_id, UserFeedHelper::getContentMessageTemplates())) {
        if (empty($stored_items['node:' . $item->get('field_ngf_created_content')[0]->target_id])) {
          $stored_items['node:' . $item->get('field_ngf_created_content')[0]->target_id] = $item;
        }
      }

      // Comment created
      if (in_array($template_id, UserFeedHelper::getCommentMessageTemplates())) {
        if (empty($stored_items['comment:' . $item->get('field_ngf_created_comment')[0]->target_id])) {
          $stored_items['comment:' . $item->get('field_ngf_created_comment')[0]->target_id] = $item;
        }
      }

      // Group created
      if (in_array($template_id, UserFeedHelper::getGroupMessageTemplates())) {
        if (empty($stored_items['node:' . $item->get('field_ngf_created_group')[0]->target_id])) {
          $stored_items['group:' . $item->get('field_ngf_created_group')[0]->target_id] = $item;
        }
      }

    }

    return $items;
  }

}
