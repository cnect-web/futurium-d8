@api @javascript
Feature: Test Groups
  I want to know if groups can be created.

  Background:
    Given a group of type "fut_open"
       | label              | Urban Agenda for the EU              |
       | fut_short_name     | Urban Agenda                         |
       | fut_description    | fut_text:fut_text:Bla Bla, Yada Yada |

  Scenario: Verify that the group homepage is accessible.
    When I go to "/group/urban-agenda-eu"
    Then I should see the text "Urban Agenda for the EU"