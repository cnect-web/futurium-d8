<?php

namespace Drupal\webtools;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;

/**
 * Class WebtoolsMapHelper.
 */
class WebtoolsMapHelper {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Drupal\Core\Render\RendererInterface definition.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;
  /**
   * Constructs a new WebtoolsMapHelper object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Creates properties array needed to display marker in map.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   * @param \Drupal\geofield\Plugin\Field\FieldType\GeofieldItem $current_item
   *   Current item/marker.
   *
   * @return array
   *   Properties array with marker.
   *
   * @throws \Exception
   */
  public function prepareMarker(ContentEntityInterface $entity, GeofieldItem $current_item) {

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    $description_elements = $view_builder->view($entity, 'map_description_default');
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
   * @param array $result
   *
   * @return array
   * @throws \Exception
   */
  public function prepareMultipleMarkers(array $result){

    $features = [];

    foreach ($result as $resultRow){
      if (isset($resultRow->_entity)){
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $resultRow->_entity;

        // todo somehow i need to make fut_event_coordinates (coordinates field configurable)
        $features[] = $this->prepareMarker($entity,  $entity->fut_event_coordinates->first());

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

  // TODO create interface for this service

}
