@mod @mod_exescorm
Feature: Scorm availability
  In order to control when a EXESCORM activity is available to students
  As a teacher
  I need be able to set availability dates for the EXESCORM

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname  | email                |
      | student1 | Student    | 1         | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user      | course | role    |
      | student1  | C1     | student |
    And the following "activities" exist:
      | activity | course | name          | packagefilepath                                | timeopen      | timeclose     |
      | exescorm    | C1     | Past EXESCORM    | mod/exescorm/tests/packages/singlesco_exescorm12.zip | ##yesterday## | ##yesterday## |
      | exescorm    | C1     | Current EXESCORM | mod/exescorm/tests/packages/singlesco_exescorm12.zip | ##yesterday## | ##tomorrow##  |
      | exescorm    | C1     | Future EXESCORM  | mod/exescorm/tests/packages/singlesco_exescorm12.zip | ##tomorrow##  | ##tomorrow##  |

  Scenario: Scorm activity with dates in the past should not be available.
    When I am on the "Past EXESCORM" "exescorm activity" page logged in as "student1"
    Then the activity date in "Past EXESCORM" should contain "Opened:"
    And the activity date in "Past EXESCORM" should contain "##yesterday noon##%A, %d %B %Y, %I:%M##"
    And the activity date in "Past EXESCORM" should contain "Closed:"
    And the activity date in "Past EXESCORM" should contain "##yesterday noon##%A, %d %B %Y, %I:%M##"
    And "Enter" "button" should not exist
    And I should not see "Preview"
    And I am on the "Current EXESCORM" "exescorm activity" page
    And the activity date in "Current EXESCORM" should contain "Opened:"
    And the activity date in "Current EXESCORM" should contain "##yesterday noon##%A, %d %B %Y, %I:%M##"
    And the activity date in "Current EXESCORM" should contain "Closes:"
    And the activity date in "Current EXESCORM" should contain "##tomorrow noon##%A, %d %B %Y, %I:%M##"
    And "Enter" "button" should exist
    And I should see "Preview"
    And I am on the "Future EXESCORM" "exescorm activity" page
    And the activity date in "Future EXESCORM" should contain "Opens:"
    And the activity date in "Future EXESCORM" should contain "##tomorrow noon##%A, %d %B %Y, %I:%M##"
    And the activity date in "Future EXESCORM" should contain "Closes:"
    And the activity date in "Future EXESCORM" should contain "##tomorrow noon##%A, %d %B %Y, %I:%M##"
    And "Enter" "button" should not exist
    And I should not see "Preview"
