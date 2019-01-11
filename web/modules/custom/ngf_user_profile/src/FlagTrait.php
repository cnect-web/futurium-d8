<?php

namespace Drupal\ngf_user_profile;

use Drupal\flag\Entity\Flag;
use Drupal\Core\Session\AccountInterface;

trait FlagTrait {
  protected function getFollowUserFlag() {
    return $this->getFlag('ngf_follow_user');
  }

  protected function getListItemFlag() {
    return $this->getFlag('ngf_list_item');
  }

  protected function getFollowContentFlag() {
    return $this->getFlag('ngf_follow_content');
  }

  protected function getFollowGroupFlag() {
    return $this->getFlag('ngf_follow_group');
  }

  protected function getFollowEventFlag() {
    return $this->getFlag('ngf_follow_event');
  }

  protected function getSavedContentFlag() {
    return $this->getFlag('ngf_save_content');
  }

  protected function getFlag($flag_id) {
    $flag = $this->flag->getFlagById($flag_id);
    if (empty($flag)) {
      throw new \LogicException(t('Flag @flag_id is not found.', ['@flag_id' => $flag_id]));
    }
    return $flag;
  }

  protected function getUserFlaggedItemsByFlagId($flag_id, $user_id) {
    $query = \Drupal::database()
      ->select('flagging', 'f')
      ->fields('f', [])
      ->condition('flag_id', $flag_id)
      ->condition('uid', $user_id);

    return $query->execute()->fetchAll();
  }

  public function getUserFlaggings(Flag $flag, AccountInterface $account = NULL) {
    $type_manager = \Drupal::entityTypeManager();
    $query = $type_manager->getStorage('flagging')->getQuery();

    $query->condition('flag_id', $flag->id());
    $query->condition('uid', $account->id());

    $ids = $query->execute();

    // sort by date (DESC).
    $flags = $type_manager->getStorage('flagging')->loadMultiple($ids);
    if (!empty($flags)) {
      usort($flags, function($a, $b) {
        $a_created = $a->get('created')->value;
        $b_created = $b->get('created')->value;
        return ($a_created > $b_created) ? -1 : 1;
      });
    }

    return $flags;
  }

}