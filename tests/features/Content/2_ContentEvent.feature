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
    Then I fill in "edit-title-0-value" with "Upcoming 5G event"
    #When I scroll "edit-fut-event-date-0" into view
    And I fill in "edit-fut-event-date-0-value" with "2019-09-26 12:00:00"
    Then I fill in wysiwyg on field "edit-fut-content-0-subform-fut-text-0-value" with "This is an example"
    Then I press the "Save" button
    And I go to "/group/broadband-networks/content/upcoming-5g-event"
    Then I break
    #Then I should see "Upcoming 5G event"
    #Then I break
    #And I should see "This is an example"
    #When I click "Edit"
    #And I fill in "edit-title-0-value" with "Upcoming 5G event edited"
    #And I fill in wysiwyg on field "edit-fut-content-0-subform-fut-text-0-value" with "This is an example edited"
    #And I press the "Save" button
    #Then I should see "Upcoming 5G event edited"
    #And I should see "This is an example edited"

  #Scenario: Test
  #  Given I am viewing a "fut_post":
  #    | title                                         | Pippo  |
  #    | edit-fut-content-0-subform-fut-text-0-value   | Pluto  |
  #  Then I break

