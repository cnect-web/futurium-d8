<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;

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
     * @When I expand operations menu with :class class
     */
    public function iExpandOperationsMenu($class)
    {
      $session = $this->getSession();
      $page = $session->getPage();
      $element = $page->findAll('css', $class);

      if (NULL === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS: "%s"', $class));
      }
    }
}
