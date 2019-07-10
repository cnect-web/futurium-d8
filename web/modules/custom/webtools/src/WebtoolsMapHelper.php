<?php

namespace Drupal\webtools;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Class WebtoolsMapHelper.
 */
class WebtoolsMapHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new WebtoolsMapHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Creates properties array needed to display marker in map.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   * @param \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem $current_item
   *   Current item/marker.
   * @param string $view_mode
   *   View mode that we use to render marker description.
   *
   * @return array
   *   Properties array with marker.
   *
   * @throws \Exception
   */
  public function prepareMarker(ContentEntityInterface $entity, GeofieldItem $current_item, $view_mode = 'map_description_default') {

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $description_elements = $view_builder->view($entity, $view_mode);
    $description = $this->renderer->render($description_elements);

    $coordinates = array_reverse($this->getCoordinates($current_item));

    $feature = [
      'properties' => [
        'name' => $entity->label(),
        'description' => $description,
      ],
      'type' => 'Feature',
      'geometry' => [
        'type' => 'point',
        'coordinates' => $coordinates,
      ],
    ];

    return $feature;
  }

  /**
   * Creates properties array for multiple markers from a view $result array.
   *
   * Here we expect that entities in result have at least one geofield.
   *
   * @param array $result
   *   Result from a view.
   *
   * @return array
   *   Array with multiple markers.
   *
   * @throws \Exception
   */
  public function prepareMultipleMarkers(array $result) {

    $features = [];

    foreach ($result as $resultRow) {
      if (isset($resultRow->_entity)) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $resultRow->_entity;

        $entity_fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());

        // Find first field of type geofield to get coordinates.
        $available_fields = array_map(function ($e) {
          return $e->getType();
        }, $entity_fields);

        $geofield = array_search('geofield', $available_fields);

        if ($geofield) {
          $features[] = $this->prepareMarker($entity, $entity->{$geofield}->first());
        }
      }
    }
    return $features;
  }

  /**
   * Get array with coordinates.
   *
   * @param \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem $item
   *   Event entity.
   *
   * @return array
   *   Array with coordinates (lat/lon).
   */
  public function getCoordinates(GeofieldItem $item) {
    return [
      $item->lat,
      $item->lon,
    ];
  }

  // TODO create interface for this service.
}
