<?php

namespace Drupal\fut_content\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;

/**
 * Example Extra field Display.
 *
 * @ExtraFieldDisplay(
 *   id = "event_map",
 *   label = @Translation("Event Map"),
 *   bundles = {
 *     "node.fut_event",
 *   }
 * )
 */
class EventMap extends ExtraFieldDisplayBase {

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {

    $elements = [];
    // First we check if we have an address on event.
    if ($this->eventHasLocation($entity) && $this->eventShowMap($entity)) {
      // Here we get the field with coordinates that will generate a map.
      $original_field = $entity->fut_event_coordinates->view([
        'type' => 'webtools_geofield_formatter',
        'label' => 'hidden',
        'settings' => [
          'map_view_mode' => 'map_description_default',
        ],
      ]);

      $elements[] = $original_field;

    }
    return $elements;
  }

  /**
   * Check if event has address.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   *
   * @return bool
   *   Return TRUE if event has Location / Coordinates.
   */
  private function eventHasLocation(ContentEntityInterface $entity) {
    if (!$entity->get('fut_event_address')->isEmpty() && !$entity->get('fut_event_coordinates')->isEmpty()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if map should be shown.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   *
   * @return bool
   *   Return TRUE if user checks "Show map" field.
   */
  private function eventShowMap(ContentEntityInterface $entity) {
    if ($entity->get('fut_event_show_map')->value) {
      return TRUE;
    }
    return FALSE;
  }

}
