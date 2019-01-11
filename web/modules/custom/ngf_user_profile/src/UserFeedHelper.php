<?php

namespace Drupal\ngf_user_profile;

class UserFeedHelper {

  public static function getContentMessageTemplates() {
    return [
      'ngf_uf_following_group_content',
      'ngf_uf_member_group_content',
      'ngf_uf_following_event_content',
      'ngf_uf_member_event_content',
      'ngf_uf_following_user_content',
    ];
  }

  public static function getCommentMessageTemplates() {
    return [
      'ngf_uf_following_user_comment',
      'ngf_uf_own_content_comment',
      'ngf_uf_following_content_comment',
    ];
  }

  public static function getGroupMessageTemplates() {
    return [
      'ngf_uf_following_user_group',
      'ngf_uf_member_group_subgroup',
      'ngf_uf_following_group_subgroup',
    ];
  }

  public static function getEventMessageTemplates() {
    return [
      'ngf_uf_following_user_event',
      'ngf_uf_member_group_event',
      'ngf_uf_following_group_event',
    ];
  }
}