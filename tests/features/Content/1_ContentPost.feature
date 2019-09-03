@api @content @javascript
Feature: Content Post
  As a developer
  I want to know if the Post content type Post can be created, edited and deleted within a Group.

  Background:
    Given I am logged in as a user with the administrator role
    And a group of type "fut_open"
      | label              | Broadband networks                   |
      | fut_short_name     | Group Name                           |
      | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |
    And I am a member of the current group

  Scenario: Verify that authenticated user can create and edit a node of type Post.
    When I go to "/group/broadband-networks"
    And I expand operations menu
    And I click "Add Post"
    Then I fill in "edit-title-0-value" with "Broadband Good Practices"
    Then I fill in wysiwyg on field "edit-fut-content-0-subform-fut-text-0-value" with "This is an example"
    And I press the "Save" button
    When I go to "/group/broadband-networks/content/broadband-good-practices"
    Then I should see "Broadband Good Practices"
    And I should see "This is an example"
    When I click "Edit"
    And I fill in "edit-title-0-value" with "Broadband Good Practices Edited"
    And I fill in wysiwyg on field "edit-fut-content-0-subform-fut-text-0-value" with "This is an example edited"
    And I press the "Save" button
    Then I should see "Broadband Good Practices Edited"
    And I should see "This is an example edited"


