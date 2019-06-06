<?php

namespace Drupal\webtools_geofield_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geofield\Plugin\Field\FieldType\GeofieldItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'webtools_geofield_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "webtools_geofield_formatter",
 *   label = @Translation("EC Webtools Map"),
 *   field_types = {
 *     "geofield"
 *   }
 * )
 */
class WebtoolsGeofieldFormatter extends FormatterBase implements ContainerFactoryPluginInterface {


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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * WebtoolsGeofieldFormatter constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder
   *   The entity view builder.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, ModuleHandlerInterface $module_handler, RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager, EntityViewBuilderInterface $view_builder, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->viewBuilder = $view_builder;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('module_handler'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.manager')->getViewBuilder('node'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'map_view_mode' => 'map_description_default',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['formatter_warning'] = [
      '#type' => 'item',
      '#title' => $this->t('Warning'),
      '#markup' => $this->t('The use of a view mode where this same formatter is used will lead to infinite loop.'),
    ];

    $form['formatter_help'] = [
      '#type' => 'item',
      '#title' => $this->t('Info'),
      '#markup' => $this->t('Make use of Map description default view mode by enabling it in CUSTOM DISPLAY SETTINGS.'),
    ];

    $form['map_view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('In map view mode'),
      '#description' => $this->t('Select a view mode to render current entity in marker description'),
      '#options' => $this->getInMapViewMode(),
      '#default_value' => $this->getSetting('in_map_view_mode'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $map_view_mode_setting = $this->getSetting('map_view_mode');
    if (!empty($map_view_mode_setting)) {
      $summary[] = $this->t('Map View Mode: @map_view_mode',
        ['@map_view_mode' => $map_view_mode_setting]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $item) {

      $path = base_path() . $this->moduleHandler->getModule('webtools_geofield_formatter')->getPath();
      $path .= '/js/fut_ec_map.js';

      $json = '{"service": "map", "version": "2.0", "custom": "' . $path . '"}';

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $item->getEntity();

      $map_identifier = $entity->bundle() . '-' . $entity->id();

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
              'ec_map' => [
                $map_identifier => [
                  'featureCollection' => $this->prepareMarker($entity, $item),
                  'center' => $this->getCoordinates($item),
                ],
              ],
            ],
          ],
        ],
      ];

      $elements = [
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

    }

    return $elements;
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

    $description_elements = $this->viewBuilder->view($entity, $this->getSetting('map_view_mode'));
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

    $feature_collection = [
      'type' => 'FeatureCollection',
      'features' => [
        $feature,
      ],
    ];

    return $feature_collection;

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

  /**
   * Get list of available view modes for current entity.
   *
   * @return array
   *   Array with enabled view modes for present entity.
   */
  private function getInMapViewMode() {
    return $this->entityDisplayRepository->getViewModeOptionsByBundle($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getTargetBundle());;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is prepared for only one value.
    if ($field_definition->getFieldStorageDefinition()->getCardinality() === 1 &&  $field_definition->getTargetEntityTypeId() == "node") {
      return TRUE;
    }
  }

}
