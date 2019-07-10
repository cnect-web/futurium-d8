@api
Feature: Groups
  As a developer
  I want to know if the group functionalities are working

  Scenario: Verify that site administrator can control the groups.
    Given I am logged in as a user with the administrator role
    And I am on "/admin"
    Then I should see the link "Reports"
    When I click "Groups"
    Then I should see the text "Group types"

