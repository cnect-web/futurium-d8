<?php

namespace Drupal\fut_content;

use Drupal\image\Entity\ImageStyle;
use Drupal\media\MediaInterface;

/**
 * Class MediaExtractor.
 */
class MediaExtractor {

  /**
   * Returns image array with image src and alt.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   A media entity holding a image.
   * @param string $image_style
   *   A image style machine name.
   *
   * @return array
   *   Array with img src and alt text.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getImageFromMedia(MediaInterface $media_entity, $image_style = 'fut_default_thumbnail') {
    $image = NULL;
    if ($img_entity_list = $media_entity->get('field_media_image')) {
      if ($img_entity = $img_entity_list->first()) {
        if ($file_entity = $img_entity->get('entity')->getTarget()) {

          $image_src = ImageStyle::load($image_style)
            ->buildUrl($file_entity
              ->get('uri')
              ->first()
              ->getString());
          $alt = $img_entity->get('alt')->getString();

          $image = [
            'src' => $image_src,
            'alt' => $alt,
          ];
        }
      }
    }

    return $image;
  }

}
