<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext
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

  /**
   * @Given the following content type :content_type
   */
  public function theFollowingContentType($content_type, TableNode $table) {
    $node = (object) $table->getRowsHash();
    $node->type = $content_type;
    $saved = $this->nodeCreate($node);
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Given the following content type :content_type in group :group_name
   */
  public function theFollowingContentTypeInGroup($content_type, $group_name, TableNode $table) {
    $node = (object) $table->getRowsHash();
    $node->type = $content_type;
    $saved = $this->nodeCreate($node);
    // @todo: add to group.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @Then I fill datetime field :locator with :value
   */
  public function iFillDateTimeField($locator, $value) {
    $el = $this->getSession()->getPage()->findField($locator);
    if (empty($el)) {
      throw new ExpectationException('Could not find DateTime with locator: ' . $locator, $this->getSession());
    }
    $fieldid = $el->getAttribute('id');
    if (empty($fieldid)) {
      throw new Exception('Could not find an id for field with locator: ' . $locator);
    }
    $this->getSession()
      ->evaluateScript("document.getElementById(\"$locator\").value = '$value'");
  }

  /**
   * @Then I click on widget :name input
   */
  public function iClickOnWidgetInput($name) {
    $this->getSession()
      ->getPage()
      ->find('named', array('button', $name))
      ->click();
  }

  /**
   * @When I scroll :elementId into view
   */
  public function scrollIntoView($elementId) {
    $function = <<<JS
(function(){
  var elem = document.getElementById("$elementId");
  elem.scrollIntoView(false);
})()
JS;
    try {
      $this->getSession()->executeScript($function);
    }
    catch(Exception $e) {
      throw new \Exception("ScrollIntoView failed");
    }
  }

}

