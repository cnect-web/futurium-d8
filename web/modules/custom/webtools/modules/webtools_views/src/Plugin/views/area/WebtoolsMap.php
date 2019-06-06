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
          'webtools' => [
            'ec_map' => []
          ],
        ],
      ],
    ];

    $map_identifier = $this->view->id() . '-' . $this->view->current_display;

    //todo this needs review how to organize it better (identifier)
    $map_properties["#attached"]['drupalSettings']['webtools']['ec_map'][$map_identifier] = [
      'featureCollection' => [
        'type' => 'FeatureCollection',
        'features' => $this->webtoolsMapHelper->prepareMultipleMarkers($this->view->result),
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

}
