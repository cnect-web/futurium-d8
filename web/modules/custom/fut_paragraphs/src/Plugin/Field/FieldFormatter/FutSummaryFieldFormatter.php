<?php

namespace Drupal\fut_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'fut_summary_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "fut_summary_field_formatter",
 *   label = @Translation("Futurium summary field formatter"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class FutSummaryFieldFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle information service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * The list of text type fields.
   *
   * @var array
   */
  public $textTypes = [
    'text_with_summary',
    'text',
    'text_long',
    'list_string',
    'string',
    'text_textarea',
  ];

  /**
   * Constructs a StringFormatter instance.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   Bundle information service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;

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
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {

      // Check from available paragraphs which one mach formatter settings.
      if ($entity->id() && $entity->bundle() == $this->getSetting('paragraph_types')) {

        $text_summary = $this->getSummary($entity);

        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => $text_summary,
        ];
        break;
      }

    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'paragraph_types' => 'fut_text',
      'char_count' => 100,
      'wordsafe' => TRUE,
      'ellipsis' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['paragraph_types'] = [
      '#type' => 'radios',
      '#title' => $this->t('Paragraph types'),
      '#description' => $this->t('Select a paragraph type to extract summary'),
      '#options' => $this->getApplicableParagraphTypes(),
      '#default_value' => $this->getSetting('paragraph_types'),
      '#required' => TRUE,
    ];

    $form['char_count'] = [
      '#title' => $this->t('Character count'),
      '#type' => 'textfield',
      '#size' => 4,
      '#maxlength' => 4,
      '#default_value' => $this->getSetting('char_count'),
      '#description' => $this->t('An upper limit on the returned string length.'),
    ];

    $form['wordsafe'] = [
      '#title' => $this->t('Wordsafe'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('wordsafe'),
      '#description' => $this->t('If true, attempt to truncate on a word boundary. Word boundaries are spaces and punctuation.'),
    ];

    $form['ellipsis'] = [
      '#title' => $this->t('Ellipsis'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('ellipsis'),
      '#description' => $this->t("If true, add '...' to the end of the truncated string."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $paragraph_types = $this->bundleInfo->getBundleInfo('paragraph');
    foreach ($paragraph_types as $paragraph_type => $label) {
      $paragraph_types[$paragraph_type] = array_shift($label);
    }

    $paragraph_types_setting = $this->getSetting('paragraph_types');
    $selected_paragraph_types = $paragraph_types[$paragraph_types_setting];
    if (!empty($selected_paragraph_types)) {
      $summary[] = $this->t('Paragraph type: @paragraph_type',
        ['@paragraph_type' => $selected_paragraph_types]);
    }

    $char_count_setting = $this->getSetting('char_count');
    if (!empty($char_count_setting)) {
      $summary[] = $this->t('Char count: @char_count',
        ['@char_count' => $char_count_setting]);
    }

    $wordsafe_setting = $this->getSetting('wordsafe');
    if (!empty($char_count_setting)) {
      $summary[] = $this->t('Word safe: @wordsafe',
        ['@wordsafe' => ($wordsafe_setting) ? 'Yes' : 'No']);
    }

    $ellipsis_setting = $this->getSetting('ellipsis');
    if (!empty($ellipsis_setting)) {
      $summary[] = $this->t('Word safe: @ellipsis',
        ['@ellipsis' => ($ellipsis_setting) ? 'Yes' : 'No']);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_type = $field_definition->getSetting('target_type');
    $paragraph_type = \Drupal::entityTypeManager()->getDefinition($target_type);
    if ($paragraph_type) {
      return $paragraph_type->isSubclassOf(ParagraphInterface::class);
    }

    return FALSE;
  }

  /**
   * Gets summary text truncated with chosen settings.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $entity
   *   Paragraph entity where we get text from.
   *
   * @return string
   *   Truncated text.
   */
  protected function getSummary(ParagraphInterface $entity) {

    $summary = '';
    $components = $this->entityTypeManager->getStorage('entity_form_display')
      ->load('paragraph.' . $entity->getType() . '.default');

    // We get the first field!!
    foreach ($components->getComponents() as $field_name => $field) {
      // Components can be extra fields, check if the field really exists.
      if (!$entity->hasField($field_name) || !in_array($field['type'], $this->textTypes)) {
        continue;
      }

      $text = $entity->get($field_name)->value;
      $summary = Unicode::truncate(trim(strip_tags($text)), $this->getSetting('char_count'), $this->getSetting('wordsafe'), $this->getSetting('ellipsis'));
    }

    return $summary;
  }

  /**
   * Finds paragraph types that has fields of text type.
   *
   * @return array
   *   List of applicable paragraph types.
   */
  protected  function getApplicableParagraphTypes() {
    $paragraph_types = $this->bundleInfo->getBundleInfo('paragraph');

    $applicable_paragraphs = [];
    foreach ($paragraph_types as $paragraph_type => $label) {

      $components = $this->entityTypeManager->getStorage('entity_form_display')
        ->load('paragraph.' . $paragraph_type . '.default');

      foreach ($components->getComponents() as $paragraph_field) {
        if (in_array($paragraph_field['type'], $this->textTypes)) {
          $applicable_paragraphs[$paragraph_type] = array_shift($label);
        }
      }
    }
    return $applicable_paragraphs;

  }

}
