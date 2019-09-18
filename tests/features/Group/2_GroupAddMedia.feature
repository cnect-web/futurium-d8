@api @content @javascript
Feature: Content Post
  As a developer
  I want to know if media document, image and video can be created within a Group.

  Background:
    Given I am logged in as a user with the administrator role
    And a group of type "fut_open"
      | label              | Broadband networks                   |
      | fut_short_name     | Group Name                           |
      | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |
    And I am a member of the current group

  Scenario: Verify that authenticated user can create media.
    When I go to "/group/broadband-networks"
    And I expand operations menu
    And I click "Add Post"
    Then I click on widget "Add Document" input
    Then I should see "Document"
    Then I click on widget "Add Image" input
    Then I should see "Image"
    Then I click on widget "Add Video" input
    Then I should see "Video"


