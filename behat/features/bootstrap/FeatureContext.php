<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context
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
     * @Given I am logged in as a user with the :arg1 role
     */
    public function iAmLoggedInAsAUserWithTheRole($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I am on :arg1
     */
    public function iAmOn($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should see the link :arg1
     */
    public function iShouldSeeTheLink($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When I click :arg1
     */
    public function iClick($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should see the text :arg1
     */
    public function iShouldSeeTheText($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I am viewing a group of type :arg1 with the title :arg2
     */
    public function iAmViewingAGroupOfTypeWithTheTitle($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @Given I am a member of the current group
     */
    public function iAmAMemberOfTheCurrentGroup()
    {
        throw new PendingException();
    }

    /**
     * @Given I view the path :arg1 relative to my current group
     */
    public function iViewThePathRelativeToMyCurrentGroup($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I am on the homepage
     */
    public function iAmOnTheHomepage()
    {
        throw new PendingException();
    }

    /**
     * @Then I see the text :arg1
     */
    public function iSeeTheText($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should not see the text :arg1
     */
    public function iShouldNotSeeTheText($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I am at :arg1
     */
    public function iAmAt($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should see the button :arg1
     */
    public function iShouldSeeTheButton($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should see the heading :arg1
     */
    public function iShouldSeeTheHeading($arg1)
    {
        throw new PendingException();
    }

}
