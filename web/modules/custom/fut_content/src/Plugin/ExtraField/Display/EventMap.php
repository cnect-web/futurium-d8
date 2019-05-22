<?php

namespace Drupal\fut_content\Plugin\ExtraField\Display;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class EventMap extends ExtraFieldDisplayBase implements ContainerFactoryPluginInterface {


  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ExtraFieldDisplayFormattedBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('module_handler'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(ContentEntityInterface $entity) {

    // First we check if we have an address on event.
    if ($this->eventHasLocation($entity) && $this->eventShowMap($entity)) {

      $path = base_path() . $this->moduleHandler->getModule('fut_content')->getPath();
      $path .= '/js/fut_event_map.js';

      $json = '{"service": "map", "version": "2.0", "custom": "' . $path . '"}';

      $map_properties = [
        '#type' => 'html_tag',
        '#tag' => 'script',
        '#attributes' => [
          'type' => 'application/json',
        ],
        '#value' => $json,
        '#attached' => [
          'library' => [
            'fut_content/webtools-smart-loader',
          ],
          'drupalSettings' => [
            'fut_content' => [
              'event_map' => [
                'featureCollection' => $this->prepareEventMarker($entity),
                'center' => $this->getEventCoordinates($entity),
              ],
            ],
          ],
        ],
      ];

      $elements = [
        '#theme' => 'event_map',
        '#attributes' => [
          'id' => 'event-map',
        ],
        'map_properties' => $map_properties,
      ];

      return $elements;
    }
  }

  /**
   * Creates properties array needed to display marker in map.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   *
   * @return array
   *   Properties array with marker.
   *
   * @throws \Exception
   */
  private function prepareEventMarker(ContentEntityInterface $entity) {
    $address_element = $entity->fut_event_address->view();
    $address = $this->renderer->render($address_element);

    $coordinates = array_reverse($this->getEventCoordinates($entity));

    $feature = [
      'properties' => [
        'name' => $entity->label(),
        'description' => $address,
      ],
      'type' => 'Feature',
      'geometry' => [
        'type' => 'point',
        'coordinates' => $coordinates,
      ],
    ];

    $feature_collection = [
      'type' => 'FeatureCollection',
      'features' => [
        $feature,
      ],
    ];

    return $feature_collection;

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
    if ($entity->get('fut_event_show_map')->isEmpty()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get array with coordinates.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Event entity.
   *
   * @return array
   *   Array with coordinates (lat/lon).
   */
  private function getEventCoordinates(ContentEntityInterface $entity) {
    return [
      $entity->fut_event_coordinates->lat,
      $entity->fut_event_coordinates->lon,
    ];
  }

}
