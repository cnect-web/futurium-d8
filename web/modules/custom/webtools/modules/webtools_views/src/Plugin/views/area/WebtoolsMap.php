<?php

namespace Drupal\webtools_views\Plugin\views\area;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Views area text handler.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("webtools_map")
 */
class WebtoolsMap extends AreaPluginBase {


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
   * The entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;


  public function __construct(array $configuration, $plugin_id, $plugin_definition,  ModuleHandlerInterface $module_handler, RendererInterface $renderer, EntityViewBuilderInterface $view_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->viewBuilder = $view_builder;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('entity_type.manager')->getViewBuilder('node')
    );
  }


  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
//    $options['content'] = [
//      'contains' => [
//        'value' => ['default' => ''],
//        'format' => ['default' => NULL],
//      ],
//    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

//    $form['content'] = [
//      '#title' => $this->t('Content'),
//      '#type' => 'text_format',
//      '#default_value' => $this->options['content']['value'],
//      '#rows' => 6,
//      '#format' => isset($this->options['content']['format']) ? $this->options['content']['format'] : filter_default_format(),
//      '#editor' => FALSE,
//    ];
  }

  /**
   * {@inheritdoc}
   */
//  public function preQuery() {
//    $content = $this->options['content']['value'];
//    // Check for tokens that require a total row count.
//    if (strpos($content, '[view:page-count]') !== FALSE || strpos($content, '[view:total-rows]') !== FALSE) {
//      $this->view->get_total_rows = TRUE;
//    }
//  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    $teste='';
    if ($empty){
      return NULL;
    }



    $path = base_path() . $this->moduleHandler->getModule('webtools_views')->getPath();
    $path .= '/js/webtools_map_views.js';

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
          'webtools/webtools-smart-loader',
        ],
        'drupalSettings' => [
          'fut_content' => [
            'ec_map' => []
          ],
        ],
      ],
    ];


//    foreach ($this->view->result as $resultRow){
//
//      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
//      $entity = $resultRow->_entity;
//
//
//      $map_identifier = $entity->bundle() . '-' . $entity->id();
//      $map_properties["#attached"]['drupalSettings']['fut_content']['ec_map'][$map_identifier] = [
//        'featureCollection' => $this->prepareMarker($entity, $entity->fut_event_coordinates->first()),
//        'center' => $this->getCoordinates($entity->fut_event_coordinates->first()),
//      ];
//
//
//    }




//    $map_properties = [
//      '#type' => 'html_tag',
//      '#tag' => 'script',
//      '#attributes' => [
//        'type' => 'application/json',
//      ],
//      '#value' => $json,
//      '#attached' => [
//        'library' => [
//          'fut_content/webtools-smart-loader',
//        ],
//        'drupalSettings' => [
//          'fut_content' => [
//            'ec_map' => [
//              $map_identifier => [
//                'featureCollection' => $this->prepareMarker($entity, $entity->fut_event_coordinates->first()),
//                'center' => $this->getCoordinates($entity->fut_event_coordinates->first()),
//              ],
//            ],
//          ],
//        ],
//      ],
//    ];


    $map_identifier = $this->view->id() . '-' . $this->view->current_display;

    //todo this needs review how to organize it better (identifier)
    $map_properties["#attached"]['drupalSettings']['fut_content']['ec_map'][$map_identifier] = [
      'featureCollection' => [
        'type' => 'FeatureCollection',
        'features' => $this->prepareMultipleMarkers($this->view->result),
      ],
      'center' => [
        40.610273,
        8.535205
      ],
    ];

    //todo missing center (for that i need a config form for this plugin)


    $map = [
      '#theme' => 'ec_map',
      '#attributes' => [
        'id' => 'ec-map-' . $map_identifier,
        'data-map-id' => $map_identifier,
        'class' => [
          'ec-map',
        ],
      ],
      'map_properties' => $map_properties,
    ];


    return $map;

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
  private function prepareMarker(ContentEntityInterface $entity, GeofieldItem $current_item) {

    $description_elements = $this->viewBuilder->view($entity, 'map_description_default');
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

  private function prepareMultipleMarkers(array $result){

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
  private function getCoordinates(GeofieldItem $item) {
    return [
      $item->lat,
      $item->lon,
    ];
  }

}
