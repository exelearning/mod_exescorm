@mod @mod_exescorm @_file_upload @_switch_iframe
Feature: Add exescorm activity
  In order to let students access a exescorm package
  As a teacher
  I need to add exescorm activity to a course

  @javascript
  Scenario: Add a exescorm activity to a course
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "EXESCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Awesome EXESCORM package |
      | Description | Description |
    And I upload "mod/exescorm/tests/packages/singlesco_exescorm12.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "Awesome EXESCORM package"
    And I should see "Enter"
    And I should see "Preview"
    And I log out
    And I am on the "Awesome EXESCORM package" "exescorm activity" page logged in as student1
    And I should see "Enter"
    And I press "Enter"
    And I switch to "exescorm_object" iframe
    And I should see "Not implemented yet"
    And I switch to the main frame
    And I am on "Course 1" course homepage
