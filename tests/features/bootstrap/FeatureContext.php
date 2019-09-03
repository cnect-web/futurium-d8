<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Exception\ExpectationException;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * Expand Group operations menu.
     *
     * @When I expand operations menu
     */
    public function iExpandOperationsMenu()
    {
      $this->getSession()->evaluateScript('jQuery(".dropbutton-multiple").addClass("open")');
    }

    /**
       * Fill text in a CKEDITOR field.
       *
       * @Then I fill in wysiwyg on field :locator with :value
       */
    public function iFillInWysiwygOnFieldWith($locator, $value) {
      $el = $this->getSession()->getPage()->findField($locator);
      if (empty($el)) {
        throw new ExpectationException('Could not find WYSIWYG with locator: ' . $locator, $this->getSession());
      }
      $fieldid = $el->getAttribute('id');
      if (empty($fieldid)) {
        throw new Exception('Could not find an id for field with locator: ' . $locator);
      }
      $this->getSession()
        ->executeScript("window.CKEDITOR.instances[\"$fieldid\"].setData(\"$value\");");
    }
}
