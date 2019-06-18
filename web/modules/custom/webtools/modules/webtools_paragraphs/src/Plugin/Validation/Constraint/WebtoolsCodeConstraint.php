<?php

namespace Drupal\webtools_paragraphs\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Plugin implementation of the 'webtools_code_constraint'.
 *
 * @Constraint(
 *   id = "webtools_code_constraint",
 *   label = @Translation("Webtools code constraint", context = "Validation"),
 * )
 */
class WebtoolsCodeConstraint extends CompositeConstraintBase {


  /**
   * The message that will be shown if the value is empty.
   *
   * @var string
   */
  public $isEmpty = '%value is empty';

  /**
   * The message that will be shown if the value is not unique.
   *
   * @var string
   */
  public $notValid = '%value is not a Webtools valid Unified Embed Code';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['field_code_snippet'];
  }

}
