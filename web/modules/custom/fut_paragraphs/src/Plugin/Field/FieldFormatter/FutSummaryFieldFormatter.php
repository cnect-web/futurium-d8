<?php

namespace Drupal\fut_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\paragraphs\ParagraphInterface;

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
class FutSummaryFieldFormatter extends EntityReferenceFormatterBase {


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity->id() &&  $entity->bundle() == "fut_text"){
        $elements[$delta] = [
          '#type' => 'markup',
          '#markup' => $entity->getSummary(),
        ];
        break;
      }

    }
    return $elements;
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

}
