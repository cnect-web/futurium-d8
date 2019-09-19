@api @javascript
Feature: Test Groups
  I want to know if groups can be created.

  Background:
    Given I am logged in as a user with the administrator role
    Given a group of type "fut_open"
       | label              | Insert Group Name Here               |
       | fut_short_name     | Group Name                           |
       | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |

  Scenario: Verify that the group appears on the site homepage.
    When I go to "/"
    Then I should see the text "Insert Group Name Here"

  Scenario: Verify that the group homepage is accessible.
    When I go to "/group/insert-group-name-here"
    Then I should see the text "Insert Group Name Here"

  # This test is failing because it is clicking the wrong "Log in" button.
  # Need to change the user block menu to a span.
  Scenario: Verify that the join button is showing.
  	Given I am logged in as a user with the "authenticated user" role
    Given I go to "/group/insert-group-name-here"
    Then I should see the text "Insert Group Name Here"
    And I should see the link "Join"
