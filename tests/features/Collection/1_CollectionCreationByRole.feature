@api @javascript
Feature: Collection
  As an Authenticated user with specific group role
  I want to know if I can create and edit collections

  Background:
    Given a group of type "fut_open"
      | label              | My fancy group                       |
      | fut_short_name     | Group Name                           |
      | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |

  Scenario: Verify that user with manager role can create Collections.
    Given I am logged in as a user with the "Authenticated user" role
    And I am a member of the current group with the role "manager"
    When I go to "/group/my-fancy-group"
    And I click "Content" in the "tabs"
    Then I click "Collections"
    And I click on widget "Add Collection" input
    Then I fill in "edit-name-0-value" with "My test collection"
    And I press the "Save" button
    Then I should see "My test collection"

  Scenario: Verify that user with editor role can not create Collections.
    Given I am logged in as a user with the "Authenticated user" role
    And I am a member of the current group with the role "editor"
    When I go to "/group/my-fancy-group"
    Then I should not see the link "Content"

  Scenario: Verify that user with moderator role can not create Collections.
    Given I am logged in as a user with the "Authenticated user" role
    And I am a member of the current group with the role "editor"
    When I go to "/group/my-fancy-group"
    Then I should not see the link "Content"
