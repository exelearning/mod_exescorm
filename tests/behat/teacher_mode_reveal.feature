@mod @mod_exescorm @_file_upload @_switch_iframe
Feature: Show the eXeLearning teacher-layer selector via the package URL parameter
  In order to let viewers show or hide the teacher-only layer of an embedded
  eXeLearning package
  As a teacher configuring the activity
  I need the plugin to make the package's own ?exe-teacher=1 selector available
  when the per-activity setting is on, and absent when it is off.

  # Upstream exelearning#1772: eXeLearning packages hide teacher-only content by
  # default and expose a selector to show it via ?exe-teacher=1. The plugin appends
  # that parameter to the SCO launch URL (the URL the package iframe is navigated to)
  # whenever the per-activity "Show teacher layer selector" setting (teachermodevisible)
  # is on — for any viewer. The plugin no longer injects CSS.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: A teacher sees the exe-teacher parameter when the reveal is on
    When the following "activities" exist:
      | activity | course | name              | packagefilepath                            | teachermodevisible |
      | exescorm | C1     | Reveal on         | mod/exescorm/tests/packages/singlescobasic.zip | 1              |
    And I am on the "Reveal on" "exescorm activity" page logged in as teacher1
    And I switch to "exescorm_object" iframe
    Then the eXeLearning content iframe url should contain "exe-teacher=1"

  @javascript
  Scenario: A teacher does not see the exe-teacher parameter when the reveal is off
    When the following "activities" exist:
      | activity | course | name              | packagefilepath                            | teachermodevisible |
      | exescorm | C1     | Reveal off        | mod/exescorm/tests/packages/singlescobasic.zip | 0              |
    And I am on the "Reveal off" "exescorm activity" page logged in as teacher1
    And I switch to "exescorm_object" iframe
    Then the eXeLearning content iframe url should not contain "exe-teacher"

  @javascript
  Scenario: A student also sees the exe-teacher parameter when the setting is on
    When the following "activities" exist:
      | activity | course | name              | packagefilepath                            | teachermodevisible |
      | exescorm | C1     | Reveal student    | mod/exescorm/tests/packages/singlescobasic.zip | 1              |
    And I am on the "Reveal student" "exescorm activity" page logged in as student1
    And I switch to "exescorm_object" iframe
    Then the eXeLearning content iframe url should contain "exe-teacher=1"
