<?php

namespace Drupal\ngf_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Image\Entity\ImageStyle;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'User Account' Block.
 *
 * @Block(
 *   id = "UserAccountBlock",
 *   admin_label = @Translation("User Account Block"),
 *   category = @Translation("User Account Block"),
 * )
 */
class UserAccountBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    if ($user = \Drupal::currentUser()) {
      return Cache::mergeTags(parent::getCacheTags(), ['user:' . $user->id()]);
    } else {
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($current_uid = \Drupal::currentUser()->id()) {

      $user = \Drupal\user\Entity\User::load($current_uid);
      $user_picture = $user->user_picture->entity;
      $picture_output = '';

      if (empty($user_picture)) {
        $field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
        $default_picture = $field->getSetting('default_image');
        $default_picture_file = \Drupal::service('entity.manager')->loadEntityByUuid('file', $default_picture['uuid']);
        $user_picture = $default_picture_file;
      }

      // The image.factory service will check if our image is valid.
      if ($user_picture) {
        $image = \Drupal::service('image.factory')->get($user_picture->getFileUri());
        $variables = [
          'style_name' => 'user_picture',
          'uri' => $user_picture->getFileUri(),
        ];
        if ($image->isValid()) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }

        $picture_output = [
          '#theme' => 'image_style',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#style_name' => $variables['style_name'],
          '#uri' => $variables['uri'],
        ];

        // Add the file entity to the cache dependencies.
        // This will clear our cache when this entity updates.
        $renderer = \Drupal::service('renderer');
        $renderer->addCacheableDependency($picture_output, $user_picture);
      }

      // Separation between code behind and presentation layer using
      // Login-account-block.html.twig.
      return [
        '#theme' => 'login_account_block',
        '#user_picture' => render($picture_output),
        '#default_user_picture' => file_create_url(drupal_get_path('theme', 'funkywave') . '/images/default_user.jpg'),
        '#user_name' => $user->getDisplayName(),
        '#user_profile_link' => '/profile/general-settings',
        '#user_manage_network_link' => '/profile/feed',
        '#user_logout_link' => '/user/logout',
      ];
    }
    else {
      return [
        '#markup' => $this->t('@login @register', [
          '@login' => Link::fromTextAndUrl(t('Login'), Url::fromRoute('user.login'))->toString(),
          '@register' => Link::fromTextAndUrl(t('Register'), Url::fromRoute('ngf_user_registration'))->toString(),
        ])
      ];
    }
  }
}
