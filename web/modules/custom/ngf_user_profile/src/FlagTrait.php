<?php

namespace Drupal\ngf_user_profile;


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
      ->fields('f', array())
      ->condition('flag_id', $flag_id)
      ->condition('uid', $user_id);

    return $query->execute()->fetchAll();
  }

}