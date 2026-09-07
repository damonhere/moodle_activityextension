@local @local_quizextensionmanager @mod_quiz
Feature: Teachers approve or deny quiz extension requests
  In order to grant or refuse extra time on a quiz
  As a teacher
  I need to review a student's pending extension request and act on it

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
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I press "Submit request"
    And I log out

  Scenario: A teacher approves an extension request and a quiz override is created
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension requests" in current page administration
    Then I should see "Student One"
    When I click on "Review" "link" in the "Student One" "table_row"
    And I should see "My laptop broke down."
    And I set the following fields to these values:
      | Comment (optional) | Approved, hope you feel better soon. |
    And I press "Approve"
    Then I should see "The request has been approved."

    When I am on the "Quiz 1" "mod_quiz > User overrides" page
    Then "Student One" "table_row" should exist

    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I follow "View your extension requests"
    Then I should not see "Pending"
    And I should see "Approved"

  Scenario: A teacher denies an extension request with a comment
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension requests" in current page administration
    And I click on "Review" "link" in the "Student One" "table_row"
    And I set the following fields to these values:
      | Comment (optional) | Please provide documentation first. |
    When I press "Deny"
    Then I should see "The request has been denied."
    And I should see "There are no pending extension requests for this quiz."

    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I follow "View your extension requests"
    Then I should see "Denied"
    And I should see "Please provide documentation first."
