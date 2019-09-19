@api @javascript
Feature: Collection
  As a developer
  I want to know if the collection functionalities are working

  Background:
    Given I am logged in as a user with the administrator role
    Given a group of type "fut_open"
      | label              | Insert Group Name Here               |
      | fut_short_name     | Group Name                           |
      | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |

  Scenario: Verify that I can create a collection.
    Given I go to "/group/insert-group-name-here"
    When I click "Content" in the "content" region
    Then I click "Collections"
    Then I click "Add Collection"
    When I fill in "edit-name-0-value" with "My test collection"
    And I press "Save"
    Then I should see "My test Collection"

