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

}
