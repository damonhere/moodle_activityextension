@local @local_quizextensionmanager @mod_quiz @javascript @_file_upload
Feature: Teachers can view a student's uploaded supporting documentation
  In order to properly evaluate an extension request
  As a teacher
  I need to see any documentation a student uploaded with their request

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
    And I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension request settings" in current page administration
    And I set the following fields to these values:
      | Documentation | Optional |
    And I press "Save changes"
    And I log out

  Scenario: A teacher can open a student's uploaded documentation from the approve modal
    Given I am on the "Quiz 1" "mod_quiz > View" page logged in as "student1"
    And I click on "Request extension" "link"
    And I set the following fields to these values:
      | requestedtimeclose[enabled] | 1                     |
      | Requested new close date    | ## +3 days ##         |
      | Reason                      | My laptop broke down. |
    And I upload "local/quizextensionmanager/tests/fixtures/test-documentation.pdf" file to "Supporting documentation" filemanager
    And I press "Submit request"
    Then I should see "Your extension request has been saved."
    And I log out

    When I am on the "Quiz 1" "mod_quiz > View" page logged in as "teacher1"
    And I navigate to "Extension requests" in current page administration
    And I click on "Approve" "link" in the "Student One" "table_row"
    Then I should see "test-documentation.pdf" in the "Approve extension request" "dialogue"
    And "test-documentation.pdf" "link" in the "Approve extension request" "dialogue" should be visible
