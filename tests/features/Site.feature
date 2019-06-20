@api @javascript
Feature: Site is installed
  Check installation
  As a developer
  I want to know if the project is installed

  Scenario: Verify that the website is accessible.
    Given I am on the homepage
    Then I see the text "Futurium"
