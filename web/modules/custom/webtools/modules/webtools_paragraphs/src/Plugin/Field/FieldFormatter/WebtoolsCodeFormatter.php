<?php

namespace Drupal\webtools_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'webtools_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "webtools_code_formatter",
 *   label = @Translation("Webtools UEC"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class WebtoolsCodeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'inline_template',
        '#template' => '{{ value|raw }}',
        '#context' => ['value' => $item->value],
        '#attached' => [
          'library' => [
            'webtools/webtools-smart-loader',
          ],
        ],
      ];
    }

    return $elements;
  }

}
