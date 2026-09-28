@quiz @quiz_essaydownload @javascript
Feature: Correct handling of groups for 5.3+

  Background:
    Given the site is running Moodle version 5.3 or higher
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | T1        | Teacher1 |
      | teacher2 | T2        | Teacher2 |
      | teacher3 | T3        | Teacher3 |
      | teacher4 | T4        | Teacher4 |
      | student1 | S1        | Student1 |
      | student2 | S2        | Student2 |
      | student3 | S3        | Student3 |
      | student4 | S4        | Student4 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | teacher        |
      | teacher3 | C1     | teacher        |
      | teacher4 | C1     | teacher        |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
    And the following "groups" exist:
      | name   | course | idnumber |
      | group1 | C1     | G1       |
      | group2 | C1     | G2       |
      | group3 | C1     | G3       |
      | group4 | C1     | G4       |
      | group5 | C1     | G5       |
    And the following "group members" exist:
      | user     | group |
      | teacher2 | G1    |
      | teacher3 | G1    |
      | teacher3 | G2    |
      | student1 | G1    |
      | student2 | G2    |
      | student3 | G2    |
      | student3 | G3    |
      | student4 | G4    |
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
    And user "student2" has attempted "Quiz 1" with responses:
      | slot | response                     |
      | 1    | The second student's answer. |
    And user "student3" has attempted "Quiz 1" with responses:
      | slot | response                                     |
      | 1    | The third student's much much longer answer. |

  Scenario: An editing teacher should see all students and all groups.
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    Then I should see "Select separate groups"
    And I should see "Attempts: 3"
    And I confirm "All participants" exists in the "Search groups" search combo box
    And I confirm "group1" exists in the "Search groups" search combo box
    And I confirm "group2" exists in the "Search groups" search combo box
    And I confirm "group3" exists in the "Search groups" search combo box
    And I confirm "group4" exists in the "Search groups" search combo box
    And I confirm "group5" exists in the "Search groups" search combo box
    When I click on "group1" in the "Search groups" search combo box
    Then I should see "Attempts: 3 (1 from this group)"

  Scenario: If a (non-editing) teacher is only in one group, they should not be able to select other groups.
    Given the site is running Moodle version 5.3 or higher
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher2"
    Then I should see "Select separate groups"
    And I confirm "group1" exists in the "Search groups" search combo box
    And I confirm "All participants" does not exist in the "Search groups" search combo box
    And I confirm "group2" does not exist in the "Search groups" search combo box
    And I confirm "group3" does not exist in the "Search groups" search combo box
    And I confirm "group4" does not exist in the "Search groups" search combo box
    And I confirm "group5" does not exist in the "Search groups" search combo box
    And I should see "Attempts: 3 (1 from this group)"

  Scenario: A (non-editing) teacher should only have access to their groups.
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher3"
    Then I should see "Select separate groups"
    And I confirm "group1" exists in the "Search groups" search combo box
    And I confirm "group2" exists in the "Search groups" search combo box
    And I confirm "All participants" does not exist in the "Search groups" search combo box
    And I confirm "group3" does not exist in the "Search groups" search combo box
    And I confirm "group4" does not exist in the "Search groups" search combo box
    And I confirm "group5" does not exist in the "Search groups" search combo box
    And I should see "Attempts: 3 (1 from this group)"
    When I click on "group2" in the "Search groups" search combo box
    Then I should see "Attempts: 3 (2 from this group)"

  Scenario: A (non-editing) teacher that is part of no groups should not have access.
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher4"
    Then I should see "Select separate groups"
    And I confirm "All participants" exists in the "Search groups" search combo box
    And I confirm "group1" does not exist in the "Search groups" search combo box
    And I confirm "group2" does not exist in the "Search groups" search combo box
    And I confirm "group3" does not exist in the "Search groups" search combo box
    And I confirm "group4" does not exist in the "Search groups" search combo box
    And I confirm "group5" does not exist in the "Search groups" search combo box
    And I should see "Attempts: 3"
    And I should see "Sorry, but you need to be part of a group to see this page."
    And I should not see "Nothing to download"

  Scenario: A (non-editing) teacher that is part of no groups, but has additional privilege, should see students.
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | moodle/site:accessallgroups | Allow      | teacher | Course       | C1        |
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher4"
    And I should see "Select separate groups"
    And I should see "Attempts: 3"
    And I confirm "group1" exists in the "Search groups" search combo box
    And I confirm "group2" exists in the "Search groups" search combo box
    And I confirm "group3" exists in the "Search groups" search combo box
    And I confirm "All participants" exists in the "Search groups" search combo box
    When I click on "group3" in the "Search groups" search combo box
    Then I should see "Attempts: 3 (1 from this group)"

  Scenario: If there are students in a group, but none attempted the quiz, the user should see a notification.
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    Then I should see "Select separate groups"
    When I click on "group4" in the "Search groups" search combo box
    Then I should see "Attempts: 3 (0 from this group)"
    And I should see "Nothing to download"

  Scenario: If there are no students in a group, the user should see a notification.
    When I am on the "Quiz 1" "quiz_essaydownload > essaydownload report" page logged in as "teacher1"
    Then I should see "Select separate groups"
    When I click on "group5" in the "Search groups" search combo box
    Then I should see "Attempts: 3 (0 from this group)"
    And I should see "Nothing to download"
    And I should not see "There are no students in this group yet"
