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

  Scenario: Verify that authenticated user can create a node of type Post.
    When I go to "/group/broadband-networks"
    And I expand operations menu with "dropbutton-multiple" class
    Then I break




