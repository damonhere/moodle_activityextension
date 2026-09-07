@local @local_quizextensionmanager @mod_quiz @javascript
Feature: Teachers approve or deny quiz extension requests
  In order to grant or refuse extra time on a quiz, without leaving the
  pending-requests dashboard
  As a teacher
  I need to review a student's pending extension request and act on it in a
  modal dialog

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

  Scenario: A teacher approves an extension request in a modal and a quiz override is created
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension requests" in current page administration
    Then I should see "Student One"
    When I click on "Approve" "link" in the "Student One" "table_row"
    And I should see "My laptop broke down." in the "Approve extension request" "dialogue"
    And I set the field "Comment (optional)" in the "Approve extension request" "dialogue" to "Approved, hope you feel better soon."
    And I click on "Approve" "button" in the "Approve extension request" "dialogue"
    Then I should not see "Approve extension request"
    And I should not see "Student One" in the "#local-quizextensionmanager-pending-table" "css_element"
    And I should see "There are no pending extension requests for this quiz."

    When I am on the "Quiz 1" "mod_quiz > User overrides" page
    Then "Student One" "table_row" should exist

    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I follow "View your extension requests"
    Then I should not see "Pending"
    And I should see "Approved"

  Scenario: A teacher denies an extension request in a modal with a comment
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension requests" in current page administration
    When I click on "Deny" "link" in the "Student One" "table_row"
    And I set the field "Comment (optional)" in the "Deny extension request" "dialogue" to "Please provide documentation first."
    And I click on "Deny" "button" in the "Deny extension request" "dialogue"
    Then I should not see "Deny extension request"
    And I should see "There are no pending extension requests for this quiz."

    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I follow "View your extension requests"
    Then I should see "Denied"
    And I should see "Please provide documentation first."
