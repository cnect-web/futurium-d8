@api
Feature: Site is installed
  Check installation
  As a developer
  I want to know if the project is installed

  Scenario: Verify that the website is accessible.
    Given I am on the homepage
    Then I see the text "NGF"

  Scenario: Check main links are accessible.
    Given I am on the homepage
    Then I should see the text "Sign In"
    And I should see the text "Join"
    And I should see the text "Discover"

  Scenario: Check login and register links aren't visible to authenticated users.
    Given I am logged in as a user with the "authenticated" role
    Then I should not see the text "Sign In"
    And I should not see the text "Join"

  Scenario: User registration fields are available.
    Given I am at "user/register"
    Then I should see the text "First name"
    And I should see the text "Last name"
    And I should see the text "Email"
    And I should see the text "Password"

  Scenario: Social media user registration options.
    Given I am at "user/login"
    Then I should see the text "Login with Facebook"
    And I should see the text "Login with LinkedIn"
    And I should see the text "Login with Google"
    And I should see the text "Login with Twitter"

  Scenario: Social media user registration options.
    Given I am at "user/register"
    Then I should see the text "Login with Facebook"
    And I should see the text "Login with LinkedIn"
    And I should see the text "Login with Google"
    And I should see the text "Login with Twitter"

  Scenario: License agreements.
    Given I am at "user/register"
    Then I should see the text "By clicking 'Sign up' or using one of the Social Auth Login buttons you agree to our Terms and Conditions and that you have read our Data Use Policy."
    And I should see the link "Terms and Conditions"
    And I should see the link "Data Use Policy"
    And I should see the button "Sign Up"

  Scenario: License agreements links.
    Given I am at "user/register"
    And I click "Terms and Conditions"
    Then I should see the heading "Terms and Conditions"

  Scenario: License agreements links.
    Given I am at "user/register"
    And I click "Data Use Policy"
    Then I should see the heading "Data Use Policy"
