<?php

namespace Drupal\webtools_paragraphs\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\webtools_paragraphs\WebtoolsCodeHelperTrait;

/**
 * Validates the WebtoolsCodeConstraint constraint.
 */
class WebtoolsCodeConstraintValidator extends ConstraintValidator {

  use WebtoolsCodeHelperTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      // First check if the value is not empty.
      if (empty($item->value)) {
        // The value is empty, so a violation, aka error, is applied.
        $this->context->addViolation($constraint->isEmpty, ['%value' => $item->value]);
      }

      // Next check if the value is a valid Webtools Unified Embed Code.
      if (!$this->isValidCode($item->value)) {
        $this->context->addViolation($constraint->notValid, ['%value' => $item->value]);
      }
    }
  }

}
