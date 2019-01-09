<?php

namespace Drupal\ngf_user_profile\Manager;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\flag\FlagService;
use Drupal\comment\CommentStatistics;
use Drupal\ngf_user_profile\FlagTrait;

class EntityUserFeedManager {

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

  /**
   * The comment statistics.
   *
   * @var Drupal\comment\CommentStatistics
   */
  protected $commentStatistics;

  protected $interests = [];
  protected $currentUserAccount = NULL;

  public function __construct(AccountInterface $current_user, FlagService $flag, $commentStatistics) {
    $this->currentUser = $current_user;
    $this->flag = $flag;
    $this->commentStatistics = $commentStatistics;
  }

  protected function getItemsNumber() {
    return 10;
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

  public function getContent() {
    $publications = $this->getPublications();
    foreach ($publications as $publication) {
      $publication->lastUpdate = $this->getChangedDate($publication);

    }

    uasort($publications, [$this, 'sortPublications']);
    return $publications;
  }

  public function sortPublications($a, $b) {
      if ($a->lastUpdate == $b->lastUpdate) {
        return 0;
      }
      return ($a->lastUpdate > $b->lastUpdate) ? -1 : 1;
  }

  protected function getGroupIdsByInterests() {
    $query = \Drupal::entityQuery('group');
    $query->condition('type', 'ngf_discussion_group');
    $query->condition('field_ngf_interests', $this->getUserInterests(), 'IN');
    return $query->execute();
  }

  protected function getGroupIdsByMembership() {
    $group_ids = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($this->getCurrentUserAccount());
    foreach ($grps as $grp) {
      $group_ids[] = $grp->getGroup()->id();
    }
    return $group_ids;
  }

  protected function getGroupPublications() {
    $groups = Group::loadMultiple($this->getGroupIds());
    $publications = [];
    foreach ($groups as $group) {
      $group_content_items = $group->getContent('group_node:ngf_discussion');
      foreach ($group_content_items as $group_content_item) {
        $publications[$group_content_item->getEntity()->id()] = $group_content_item->getEntity();
      }
    }
    return $publications;
  }

  protected function getPublications() {
    return array_unique(
      array_merge(
        $this->getPublicationsByInterests(),
        $this->getGroupPublications(),
        $this->getFollowedPublications()
      ), SORT_REGULAR
    );
  }

  protected function getPublicationsByInterests() {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'ngf_discussion');
    $query->condition('field_ngf_interests', $this->getUserInterests(), 'IN');
    return Node::loadMultiple($query->execute());
  }

  protected function getFollowedPublications() {
    $flaggings = $this->getUserFlaggedItemsByFlagId('ngf_follow_content');
    $publications = [];
    foreach ($flaggings as $flagging) {
      $publications[] =  Node::load($flagging->entity_id);
    }
    return $publications;
  }

  protected function getFollowedGroupIds() {
    $flaggings = $this->getUserFlaggedItemsByFlagId('ngf_follow_group');
    $group_ids = [];
    foreach ($flaggings as $flagging) {
      $group_ids[$flagging->entity_id] = $flagging->entity_id;
    }
    return $group_ids;
  }

  protected function getGroupIds() {
    return array_unique(
      array_merge(
        $this->getGroupIdsByInterests(),
        $this->getGroupIdsByMembership(),
        $this->getFollowedGroupIds()
      )
    );
  }

  protected function getEvents() {

  }

  protected function getPublicationByInterests() {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'ngf_discussion');
    $query->condition('field_ngf_interests', $this->getUserInterests(), 'IN');
    $query->condition('uid', $this->currentUser->id(), '<>');
    return $query->execute();
  }

  protected function getChangedDate($node) {
    $changed = $node->getChangedTime();
    $latest_comment = $this->commentStatistics->read([$node->id() => $node], 'node', FALSE);
    if ($latest_comment && $latest_comment->last_comment_timestamp > $changed) {
      $changed = $latest_comment->last_comment_timestamp;
    }
    return $changed;
  }

}
