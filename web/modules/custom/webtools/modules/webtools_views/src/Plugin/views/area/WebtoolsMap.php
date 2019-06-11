<?php

namespace Drupal\webtools_views\Plugin\views\area;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\webtools\WebtoolsMapHelper;
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
   * The Webtools Map helper service.
   *
   * @var \Drupal\webtools\WebtoolsMapHelper
   */
  protected $webtoolsMapHelper;

  /**
   * WebtoolsMap constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\webtools\WebtoolsMapHelper $webtools_map_helper
   *   The Webtools Map Helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, WebtoolsMapHelper $webtools_map_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->webtoolsMapHelper = $webtools_map_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('webtools.webtools_map_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['zoom'] = [
      'contains' => [
        'initial_zoom' => ['default' => 4],
        'min_zoom' => ['default' => 2],
        'max_zoom' => ['default' => 10],
      ],
    ];

    $options['map_center'] = [
      'contains' => [
        'center_lat' => ['default' => 50.84],
        'center_lon' => ['default' => 4.36],
        'fitbounds' => ['default' => 1],
      ],
    ];

    $options['tile'] = ['default' => 'osmec'];

    $options['height'] = ['default' => 430];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Defines zoom options.
    $zoom_options = [
      0 => $this->t('0 - Low/Far'),
      18 => $this->t('18 - High/Close'),
    ];

    for ($i = 1; $i < 18; $i++) {
      $zoom_options[$i] = $i;
    }

    ksort($zoom_options);

    $form['zoom'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Zoom'),
      'initial_zoom' => [
        '#title' => $this->t('Initial zoom level'),
        '#description' => $this->t('The starting zoom level when this map is rendered.  Restricted by min and max zoom settings.'),
        '#type' => 'select',
        '#options' => $zoom_options,
        '#default_value' => $this->options['zoom']['initial_zoom'],
      ],
      'min_zoom' => [
        '#title' => $this->t('Minimum zoom level'),
        '#description' => $this->t('The minimum zoom level allowed. (How far away can you view from?)'),
        '#type' => 'select',
        '#options' => $zoom_options,
        '#default_value' => $this->options['zoom']['min_zoom'],
      ],
      'max_zoom' => [
        '#title' => $this->t('Maximum zoom level'),
        '#description' => $this->t('The maximum zoom level allowed. (How close in can you get?).'),
        '#type' => 'select',
        '#options' => $zoom_options,
        '#default_value' => $this->options['zoom']['max_zoom'],
      ],
      'info' => [
        '#markup' => $this->t('Please check <a href=":information" target="_blank">Map - Available tile services</a>info page for more inf on tiles and max zoom.', [':information' => 'https://webgate.ec.europa.eu/fpfis/wikis/display/webtools/Map+-+Available+tile+services']),
      ],
    ];

    $form['map_center'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map center'),
      '#description' => $this->t('Center of the map. E.g. latitude 50.84 and 4.36 longitude for Brussels'),
      'center_lat' => [
        '#title' => $this->t('Latitude'),
        '#type' => 'textfield',
        '#default_value' => $this->options['map_center']['center_lat'],
      ],
      'center_lon' => [
        '#title' => $this->t('Longitude'),
        '#type' => 'textfield',
        '#default_value' => $this->options['map_center']['center_lon'],

      ],
      'fitbounds' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Fit map to visible markers'),
        '#description' => $this->t('This sets the center of the map automatically based on the visible markers. It ignores the map center coordinates set above.'),
        '#default_value' => $this->options['map_center']['fitbounds'],
      ],
    ];

    $form['tile'] = [
      '#type' => 'select',
      '#title' => t('Tiles'),
      '#description' => t('Map background'),
      '#options' => [
        'osmec' => 'Open Street Map customised for European Commission (Max zoom 18)',
        'graybg' => 'Gray background with country outlines (Max zoom 8)',
        'coast' => 'Gray background with continent outlines (Max zoom 11)',
        'gray' => 'Gray shaded relief of earth (Max zoom 6)',
        'hypso' => 'Climate shaded relief of earth (Max zoom 6)',
        'natural' => 'Landcover shaded relief of earth (Max zoom 6)',
        'bmarble' => 'Satellite  images of earth (Max zoom 7)',
        'copernicus003' => 'Copernicus Core003 mosaic (Max zoom 16)',
        'countryboundaries_world' => 'Country boundaries world (Max zoom 12)',
        'roadswater_europe' => 'Roads and waterways Europe (Max zoom 12)',
        'countrynames_europe' => 'Country names Europe (Max zoom 12)',
        'citynames_europe' => 'City names Europe (Max zoom 12)',
        'sentinelcloudless' => 'Sentinel Cloudless (Max zoom 18)',
      ],
      '#default_value' => $this->options['tile'],
    ];

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#description' => $this->t('To ensure the map menu displays correctly, it is recommended to choose a height higher than 300px.'),
      '#field_suffix' => $this->t('px'),
      '#default_value' => $this->options['height'],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {

    if ($empty) {
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
          'webtools' => [
            'ec_map' => [],
          ],
        ],
      ],
    ];

    $map_identifier = $this->view->id() . '-' . $this->view->current_display;

    $map_properties["#attached"]['drupalSettings']['webtools']['ec_map'][$map_identifier] = [
      'featureCollection' => [
        'type' => 'FeatureCollection',
        'features' => $this->webtoolsMapHelper->prepareMultipleMarkers($this->view->result),
      ],
      'zoom' => [
        'initial_zoom' => $this->options['zoom']['initial_zoom'],
        'min_zoom' => $this->options['zoom']['min_zoom'],
        'max_zoom' => $this->options['zoom']['max_zoom'],
      ],
      'center' => [
        $this->options['map_center']['center_lat'],
        $this->options['map_center']['center_lon'],
      ],
      'fitbounds' => $this->options['map_center']['fitbounds'],
      'tile' => $this->options['tile'],
      'height' => $this->options['height'],

    ];

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

}
