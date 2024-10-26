@quiz @quiz_essaydownload @javascript
Feature: Validation and display of the form

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | T1        | Teacher1 |
      | student1 | S1        | Student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity | name   | intro              | course | groupmode |
      | quiz     | Quiz 1 | Quiz 1 description | C1     | 1         |
    And the following "questions" exist:
      | questioncategory | qtype | name | questiontext   |
      | Test questions   | essay | Q1   | First question |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | Q1       | 1    | 1.0     |
    And user "student1" has attempted "Quiz 1" with responses:
      | slot | response                    |
      | 1    | The first student's answer. |

  Scenario: Invalid form values should trigger an error message
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    And I set the field "marginleft" to "100"
    And I press "Download"
    And I wait until the page is ready
    Then I should see "All page margins must be integers between 0 and 80."
    When I set the following fields to these values:
      | marginleft  | 20 |
      | marginright | -1 |
    And I press "Download"
    And I wait until the page is ready
    Then I should see "All page margins must be integers between 0 and 80."
    When I set the following fields to these values:
      | marginleft  | 20 |
      | marginright | 20 |
      | fontsize    | 5  |
    And I press "Download"
    And I wait until the page is ready
    Then I should see "Font size should be an integer between 6 and 50."

  Scenario: PDF specific fields should be disabled if output set to TXT
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    When I set the field "fileformat" to "txt"
    Then the "source" "select" should be disabled
    And the "fixremfontsize" "field" should be disabled
    And the "page" "select" should be disabled
    And the "marginleft" "field" should be disabled
    And the "marginright" "field" should be disabled
    And the "margintop" "field" should be disabled
    And the "marginbottom" "field" should be disabled
    And the "includefooter" "field" should be disabled
    And the "linespacing" "select" should be disabled
    And the "font" "select" should be disabled
    And the "fontsize" "field" should be disabled

  Scenario: Font size workaround should be disabled if source is summary
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    When I set the field "source" to "plain"
    Then the "fixremfontsize" "field" should be disabled
