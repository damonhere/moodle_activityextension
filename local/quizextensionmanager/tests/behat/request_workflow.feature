@local @local_quizextensionmanager @mod_quiz
Feature: Students request a quiz time extension
  In order to get more time on a quiz when I need it
  As a student
  I need to submit, edit and cancel extension requests from the quiz page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity | name   | course | idnumber | timeopen      | timeclose    | timelimit | attempts |
      | quiz     | Quiz 1 | C1     | quiz1    | ##yesterday## | ##tomorrow## | 3600      | 2        |

  Scenario: A student submits a new extension request
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    Then I should see "Request extension"
    When I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                       |
      | Requested new close date    | ## +3 days ##           |
      | Reason                      | My laptop broke down.   |
    And I press "Submit request"
    Then I should see "Your extension request has been saved."
    And I should see "Pending"
    And I should see "My laptop broke down." in the "generaltable" "table"

  Scenario: A student edits a pending extension request
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I press "Submit request"
    When I click on "Edit" "link"
    And I set the following fields to these values:
      | Reason | My laptop broke down and I lost my notes too. |
    And I press "Submit request"
    Then I should see "Your extension request has been saved."
    And I should see "My laptop broke down and I lost my notes too." in the "generaltable" "table"

  Scenario: A student cancels a pending extension request
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I press "Submit request"
    When I click on "Cancel" "link"
    Then I should see "Are you sure you want to cancel this extension request?"
    And I press "Continue"
    Then I should see "Your extension request has been cancelled."
    And I should see "Cancelled"

  Scenario: A pending request blocks submitting a second request for the same quiz
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I press "Submit request"
    When I am on the "Quiz 1" "mod_quiz > View" page
    Then I should not see "Request extension"
    And I should see "You have a pending extension request"

  Scenario: Two different students can each have their own pending request
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I press "Submit request"
    And I log out
    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "student2"
    Then I should see "Request extension"
