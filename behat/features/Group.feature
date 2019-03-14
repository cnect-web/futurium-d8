@api
Feature: Groups
  As a developer
  I want to know if the group functionalities are working

  Scenario: Verify that authenticated users can create groups.
    Given I am logged in as a user with the "authenticated" role
    And I am on "/discover"
    Then I should see the link "Create a Group"
    When I click "Create a Group"
    Then I should see the text "Add group"

  Scenario: Verify that authenticated users can create group content.
    Given I am logged in as a user with the "authenticated" role
    And I am viewing a group of type "ngf_discussion_group" with the title "My Discussion Group"
    And I am a member of the current group
    And I view the path '/' relative to my current group
    Then I should see the text "New Discussion"
