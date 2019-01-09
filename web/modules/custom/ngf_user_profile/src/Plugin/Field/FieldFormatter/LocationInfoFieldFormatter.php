<?php

namespace Drupal\ngf_user_profile\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'location_info_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "ngf_location_info_field_formatter",
 *   label = @Translation("Location info"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class LocationInfoFieldFormatter extends FormatterBase {

  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return (!empty($field_definition->getSetting('handler_settings')['target_bundles']) && in_array('ngf_cities', $field_definition->getSetting('handler_settings')['target_bundles']));
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $output = '';
    if (!$item->getEntity()->get('field_ngf_city')->isEmpty()) {
      $city = $item->getEntity()->get('field_ngf_city')->entity;
      $output .= $city->getName();
      if (!$city->get('field_ngf_country')->isEmpty()) {
        $output .= ' - ' . $city->get('field_ngf_country')->entity->get('field_ngf_iso_code')->value;
      }
    }
    return nl2br(Html::escape($output));
  }

}
