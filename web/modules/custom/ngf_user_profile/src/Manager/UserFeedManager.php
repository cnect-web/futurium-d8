<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\comment\Entity\Comment;
use Drupal\flag\FlagService;
use Drupal\ngf_user_profile\FlagTrait;
use Drupal\message\Entity\Message;
use Drupal\ngf_user_profile\UserFeedHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class UserFeedManager {

  use FlagTrait;

  /**
   * Entity Type Manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager = NULL;

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

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    FlagService $flag
  ) {
    $this->entityTypeManager = $entity_type_manager;
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

  public function createContentUserFeed(Node $node) {
    $this->createUserFeedFollowingUserNewContent($node);
  }

  public function createGroupContentUserFeeds(GroupContent $group_content) {
    // Probably in the future we will need specific content type.
    // $group_content->getGroupContentType()->getPluginId() == 'group_node:ngf_discussion'.
    // or we will need specific group type
    // $group_content->getGroupContentType()->getGroupType() == 'ngf_discussion_group'.
    if ($group_content->getContentPlugin()->getPluginId() == 'group_node:ngf_discussion') {
      $this->createUserFeedFollowingGroupNewContent($group_content);
      $this->createUserFeedMemberGroupNewContent($group_content);
    }
    else {
      $this->createUserFeedFollowingGroupNewSubgroup($group_content);
      $this->createUserFeedMemberGroupNewSubgroup($group_content);
    }
  }

  public function createGroupUserFeeds(Group $group) {
    $this->createUserFeedFollowingUserNewGroup($group);
  }

  protected function createUserFeedFollowingContentNewComment(Comment $comment) {
    // Create a user feed for users who follow the commented content.
    $this->createMessage('ngf_uf_following_content_comment', [
      'field_ngf_users' => $this->getUsersFollowingContent($comment->getCommentedEntity()),
      'field_ngf_created_comment' => $comment,
      'field_ngf_following_content' => $comment->getCommentedEntity(),
    ]);
  }

  protected function createUserFeedOwnContentNewComment(Comment $comment) {
    // If author of the comment and author of the content are not the same.
    $this->createMessage('ngf_uf_own_content_comment', [
      'field_ngf_users' => [$comment->getCommentedEntity()->getOwner()],
      'field_ngf_created_comment' => $comment,
      'field_ngf_following_content' => $comment->getCommentedEntity(),
    ]);
  }

  protected function createUserFeedFollowingUserNewComment(Comment $comment) {
    // Create a user feed for the following user who commented something.
    $this->createMessage('ngf_uf_following_user_comment', [
      'field_ngf_users' => $this->getUsersFollowingUser($comment->getOwner()),
      'field_ngf_created_comment' => $comment,
      'field_ngf_following_user' => $comment->getOwner(),
      'field_ngf_commented_content' => $comment->getCommentedEntity(),
    ]);
  }

  protected function createUserFeedFollowingUserNewContent(Node $node) {
    // Create a user feed for users who follow the user who has created content.
    $this->createMessage('ngf_uf_following_user_content', [
      'field_ngf_users' => $this->getUsersFollowingUser($node->getOwner()),
      'field_ngf_created_content' => $node,
      'field_ngf_following_user' => $node->getOwner(),
    ]);
  }

  protected function createUserFeedFollowingGroupNewContent(GroupContent $group_content) {
    // Create a user feed for users who follow the group where content
    // was created.
    $template = NULL;
    $group_type = $group_content->getGroupContentType()->getGroupType();
    $group = $group_content->getGroup();

    if ($group_type == 'ngf_discussion_group') {
      $template = 'ngf_uf_following_group_content';
      $users = $this->getUsersFollowingGroup($group);
    }
    else if ($group_type == 'ngf_event') {
      $template = 'ngf_uf_following_event_content';
      $users = $this->getUsersFollowingEvent($group);
    }

    if (!empty($template)) {
      $this->createMessage('ngf_uf_following_group_content', [
        'field_ngf_users' => $users,
        'field_ngf_created_content' => $group_content->getEntity(),
        'field_ngf_following_group' => $group,
      ]);
    }
  }

  protected function createUserFeedFollowingGroupNewSubgroup(GroupContent $group_content) {
    // Create a user feed for users who follow the group where subgroup or event
    // was created.
    $template = NULL;
    $group_type = $group_content->getGroupContentType()->getGroupType();
    $group = $group_content->getGroup();

    if ($group_type == 'ngf_discussion_group') {
      $template = 'ngf_uf_following_group_subgroup';
      $users = $this->getUsersFollowingGroup($group);
    }
    else if ($group_type == 'ngf_event') {
      $template = 'ngf_uf_following_group_event';
      $users = $this->getUsersFollowingEvent($group);
    }

    if (!empty($template)) {
      $this->createMessage($template, [
        'field_ngf_users' => $users,
        'field_ngf_created_group' => $group_content->getEntity(),
        'field_ngf_following_group' => $group,
      ]);
    }
  }

  protected function createUserFeedMemberGroupNewContent(GroupContent $group_content) {
    // Create a user feed for members of the group where content
    // was created.
    $template = NULL;
    $group_type = $group_content->getGroupContentType()->getGroupType();
    $group = $group_content->getGroup();

    if ($group_type == 'ngf_discussion_group') {
      $template = 'ngf_uf_member_group_subgroup';
    }
    else if ($group_type == 'ngf_event') {
      $template = 'ngf_uf_member_event_content';
    }

    if (!empty($template)) {
      $this->createMessage('ngf_uf_member_group_content', [
        'field_ngf_users' => $group->getContentEntities('group_membership'),
        'field_ngf_created_content' => $group_content->getEntity(),
        'field_ngf_following_group' => $group,
      ]);
    }
  }

  protected function createUserFeedMemberGroupNewSubgroup(GroupContent $group_content) {
    // Create a user feed for members of the group where subgroup or event
    // was created.
    $template = NULL;
    $group_type = $group_content->getGroupContentType()->getGroupType();
    $group = $group_content->getGroup();

    if ($group_type == 'ngf_discussion_group') {
      $template = 'ngf_uf_member_group_subgroup';
    }
    else if ($group_type == 'ngf_event') {
      $template = 'ngf_uf_member_group_event';
    }

    if (!empty($template)) {
      $this->createMessage($template, [
        'field_ngf_users' => $group->getContentEntities('group_membership'),
        'field_ngf_created_group' => $group_content->getEntity(),
        'field_ngf_following_group' => $group,
      ]);
    }
  }

  protected function createUserFeedFollowingUserNewGroup(Group $group) {
    // Create a user feed for users following the user who has created
    // a group or an event.
    $template_id = NULL;
    if ($group->getGroupType()->id() == 'ngf_discussion_group') {
      $template_id = 'ngf_uf_following_user_group';
    }
    else if ($group->getGroupType()->id() == 'ngf_event') {
      $template_id = 'ngf_uf_following_user_event';
    }
    if (!empty($template_id)) {
      $creator = $group->getOwner();
      $this->createMessage($template_id, [
        'field_ngf_users' => $this->getUsersFollowingUser($creator),
        'field_ngf_created_group' => $group,
        'field_ngf_following_user' => $creator,
      ]);
    }
  }

  protected function createMessage($template, array $fields) {
    if (!empty($fields['field_ngf_users'])) {
      $message = Message::create([
        'template' => $template
      ]);
      foreach ($fields as $field_name => $field_value) {
        $message->set($field_name, $field_value);
      }
      $message->save();
    }
  }

  protected function getUsersFollowingContent(Node $node) {
    return $this->getUsersFollowingEntity($node, $this->getFollowContentFlag());
  }

  protected function getUsersFollowingGroup(Group $group) {
    return $this->getUsersFollowingEntity($group, $this->getFollowGroupFlag());
  }

  protected function getUsersFollowingUser(User $user) {
    return $this->getUsersFollowingEntity($user, $this->getFollowUserFlag());
  }

  protected function getUsersFollowingEvent(Group $group) {
    return $this->getUsersFollowingEntity($group, $this->getFollowEventFlag());
  }

  protected function getUsersFollowingEntity($entity, $flag) {
    $author = $entity instanceof User ? $entity : $entity->getOwner();
    return $this->removeAuthor($this->flag->getFlaggingUsers($entity, $flag), $author);
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

  public function getContent() {
    $publications = $this->getFeedItems();
    if (count($publications) > 0) {
      $page = pager_find_page();
      $num_per_page = 10;
      $offset = $num_per_page * $page;
      $result = array_slice($publications, $offset, $num_per_page);

      // Now that we have the total number of results, initialize the pager.
      pager_default_initialize(count($publications), $num_per_page);

      // Create a render array with the search results.
      $render = [];
      // TODO: Move thi title to proper place.
      $render['#prefix'] = '<h3>' . t('Your feed') . '</h3><div class="newsfeed">';
      $render['#suffix'] = '</div>';

      foreach ($result as $item) {
        $message = $this->entityTypeManager->getViewBuilder('message')
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
      $render[] = [
        '#theme' => 'markup',
        '#markup' => '<p>' . t('There are no items in your feed') . '</p>',
      ];
    }

    return $render;
  }

  public function getFeedItems() {
    $query = \Drupal::entityQuery('message');
    $query->condition('template', [
      'ngf_uf_following_content_comment',
      'ngf_uf_own_content_comment',
      'ngf_uf_following_user_comment',
      'ngf_uf_following_user_content',
      'ngf_uf_following_user_group',
      'ngf_uf_following_user_event',
      'ngf_uf_following_group_content',
      'ngf_uf_following_group_subgroup',
      'ngf_uf_following_group_event',
      'ngf_uf_following_event_content',
      'ngf_uf_member_event_content',
      'ngf_uf_member_group_subgroup',
      'ngf_uf_member_group_event',
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
        if (empty($stored_items['node:' . $item->get('field_ngf_created_content')->target_id])) {
          $stored_items['node:' . $item->get('field_ngf_created_content')->target_id] = $item;
        }
      }

      // Comment created
      if (in_array($template_id, UserFeedHelper::getCommentMessageTemplates())) {
        if (empty($stored_items['comment:' . $item->get('field_ngf_created_comment')->target_id])) {
          $stored_items['comment:' . $item->get('field_ngf_created_comment')->target_id] = $item;
        }
      }

      // Group created
      if (in_array($template_id, UserFeedHelper::getGroupMessageTemplates())) {
        if (empty($stored_items['group:' . $item->get('field_ngf_created_group')->target_id])) {
          $stored_items['group:' . $item->get('field_ngf_created_group')->target_id] = $item;
        }
      }

      // Event created
      if (in_array($template_id, UserFeedHelper::getEventMessageTemplates())) {
        if (empty($stored_items['event:' . $item->get('field_ngf_created_group')->target_id])) {
          $stored_items['event:' . $item->get('field_ngf_created_group')->target_id] = $item;
        }
      }

    }

    return $stored_items;
  }

}
