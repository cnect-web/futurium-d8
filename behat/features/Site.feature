@api
Feature: Site is installed
  Check installation
  As a developer
  I want to know if the project is installed

  Scenario: Verify that the website is accessible.
    Given I am on the homepage
    Then I see the text "Futurium"

  Scenario: Check main links are accessible.
    Given I am on the homepage
    Then I should see the text "Log In"
    And I should see the text "Register"
    And I should see the text "Contact"

  Scenario: Check login and register links aren't visible to authenticated users.
    Given I am logged in as a user with the "authenticated" role
    Then I should not see the text "Log In"
    And I should not see the text "Register"

  Scenario: User registration fields are available.
    Given I am at "user/register"
    Then I should see the text "Email address"
    And I should see the text "Username"
    And I should see the text "Picture"
    And I should see the text "Contact settings"
