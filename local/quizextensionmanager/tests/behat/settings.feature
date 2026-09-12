@local @local_quizextensionmanager @mod_quiz
Feature: Teachers configure per-quiz extension request settings
  In order to control whether students can request extensions
  As a teacher
  I need to enable or disable extension requests for a specific quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activities" exist:
      | activity | name   | course | idnumber | timeopen      | timeclose    | timelimit | attempts |
      | quiz     | Quiz 1 | C1     | quiz1    | ##yesterday## | ##tomorrow## | 3600      | 2        |

  Scenario: Extension requests are allowed by default
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    Then I should see "Request extension"

  Scenario: A teacher does not see the student-facing "Request extension" link
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    Then I should not see "Request extension"

  # @javascript is required here, not optional: unchecking an advcheckbox
  # (a real checkbox plus a same-named hidden "0" fallback, so unchecked
  # boxes still submit something) is broken under the non-JS BrowserKit
  # driver -- confirmed via a debug dump showing $_POST['enabled'] entirely
  # absent when this scenario ran without @javascript. A real browser
  # (WebDriver) submits the hidden fallback correctly; BrowserKit's form
  # parser loses it when two same-named inputs exist. Not a plugin bug.
  @javascript
  Scenario: A teacher disables extension requests for a quiz
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension request settings" in current page administration
    When I set the following fields to these values:
      | Allow extension requests for this quiz | 0 |
    And I press "Save changes"
    And I log out
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    Then I should not see "Request extension"

  # See the comment on the previous scenario -- same advcheckbox/BrowserKit
  # limitation applies here.
  @javascript
  Scenario: A teacher re-enables extension requests for a quiz
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension request settings" in current page administration
    And I set the following fields to these values:
      | Allow extension requests for this quiz | 0 |
    And I press "Save changes"
    And I navigate to "Extension request settings" in current page administration
    And I set the following fields to these values:
      | Allow extension requests for this quiz | 1 |
    And I press "Save changes"
    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    Then I should see "Request extension"
