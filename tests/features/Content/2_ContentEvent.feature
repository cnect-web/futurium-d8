@api @content @javascript @date
Feature: Content Event
  As a developer
  I want to know if the Event content type can be created and edited within a Group.

  Background:
    Given I am logged in as a user with the administrator role
    And a group of type "fut_open"
      | label                     | Broadband networks                   |
      | fut_short_name            | Group Name                           |
      | fut_description           | fut_text:fut_text:Bla Bla, Yada Yada |
    And the following content type "fut_event"
      | title                     | Reducing pollution in 2016 Meeting    |
      | fut_event_date:value      | 2019-09-10T13:41:45                   |
      | fut_event_date:end_value  | 2019-09-10T13:41:46                   |
    And I am a member of the current group

  Scenario: Verify that authenticated user can create and edit a node of type Event.
    Then I should see "Reducing pollution in 2016 Meeting"
