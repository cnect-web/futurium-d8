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
    When I go to "/group/broadband-networks"
    And I expand operations menu
    And I click "Add Event"
    Then I fill in "edit-title-0-value" with "Broadband Good Practices"
    Then I fill datetime field "edit-fut-event-date-0-end-value" with "2020-09-10 13:41:45"
    Then I fill in wysiwyg on field "edit-fut-content-0-subform-fut-text-0-value" with "This is an example"
    Then I press the "Save" button
    Then I go to "/group/broadband-networks/content/broadband-good-practices"
    Then I should see "Broadband Good Practices"
