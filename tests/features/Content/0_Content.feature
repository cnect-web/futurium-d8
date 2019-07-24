@api @content
Feature: Content
  Check that all content types are functional
  As a developer
  I want to know if the content types are installed

  Scenario Outline: Verify that site administrator can add content post or event.
    Given I am logged in as a user with the administrator role
    And I go to "<path>"
    Then I should see the link link

    Examples:
      | path              | link                  |
      | node/add          | Event                 |
      | node/add          | Post                  |



