@mod @mod_exescorm @_file_upload @_switch_iframe
Feature: Reveal eXeLearning teacher content via the package URL parameter
  In order to keep teacher-only content hidden from students by default
  As a teacher
  I need the plugin to reveal teacher content only for me, via the package's own
  ?exe-teacher=1 URL parameter, and never for students.

  # Upstream exelearning#1772: eXeLearning packages hide teacher-only content by
  # default and reveal it via ?exe-teacher=1. The plugin appends that parameter to
  # the SCO launch URL (the URL the package iframe is navigated to) only for users
  # who can manage the activity AND when the per-activity "Reveal teacher content to
  # teachers" setting (teachermodevisible) is on. The plugin no longer injects CSS.

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
  Scenario: A student never sees the exe-teacher parameter even when the reveal is on
    When the following "activities" exist:
      | activity | course | name              | packagefilepath                            | teachermodevisible |
      | exescorm | C1     | Reveal student    | mod/exescorm/tests/packages/singlescobasic.zip | 1              |
    And I am on the "Reveal student" "exescorm activity" page logged in as student1
    And I switch to "exescorm_object" iframe
    Then the eXeLearning content iframe url should not contain "exe-teacher"
