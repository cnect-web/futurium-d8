<?php

/**
 *
 */

namespace Drupal\ngf_user_profile;

use Drupal\message\entity\Message;

class UserFeedItem {
  protected $userFullName = NULL;
  protected $userPictureName = NULL;
  protected $userUrl = NULL;
  protected $contextText = NULL;
  protected $entity = NULL;
  protected $message = NULL;

  public function __construct(Message $message) {
    if ($message) {
      $template_id = $message->getTemplate()->id();
      $text = $message->getText();
      $this->contextText = !empty($text) > 0 ? (string)array_shift($text) : NULL;
      $entityView = NULL;
      $user = NULL;

      if (in_array($template_id, UserFeedHelper::getContentMessageTemplates())) {
        $node = $message->get('field_ngf_created_content')->entity;
        if (!empty($node)) {
          $this->entity = \Drupal::entityTypeManager()
            ->getViewBuilder('node')
            ->view($node, 'teaser');
          $user = $node->getOwner();
        }
      }

      if (in_array($template_id, UserFeedHelper::getCommentMessageTemplates())) {
        $comment = $message->get('field_ngf_created_comment')->entity;
        if (!empty($comment)) {
          $this->entity = \Drupal::entityTypeManager()
            ->getViewBuilder('node')
            ->view($comment->getCommentedEntity(), 'teaser');
          $user = $comment->getOwner();
        }
      }

      if (in_array($template_id, UserFeedHelper::getGroupMessageTemplates())) {
        $group = $message->get('field_ngf_created_group')->entity;
        if (!empty($group)) {
          $this->entity = \Drupal::entityTypeManager()
            ->getViewBuilder('group')
            ->view($group, 'teaser');
          $user = $group->getOwner();
        }
      }

      if ($user) {
        $user->get('full_name');
        $this->userFullName = $user->get('full_name')->value;

        $user_image = [
          '#theme' => 'image_style',
          '#style_name' => 'thumbnail',
          '#uri' => $user->get('user_picture')->entity->getFileUri(),
          '#alt' => $this->userFullName,
          '#attributes' => [
            'class' => [
              'post-info__link post-info__link--account'
            ]
          ]
        ];
        $this->userPictureName = render(
          $user_image
        );
        $this->userUrl = $user->toUrl()->toString();
      }
    }
  }

  public function getView() {
    return [
      '#theme' => 'ngf_user_feed_item',
      '#authorFullName' => $this->userFullName,
      '#authorProfileURL' => $this->userUrl,
      '#authorPics' => $this->userPictureName,
      '#entity' => $this->entity,
      '#contextText' => $this->contextText,
    ];
  }
}