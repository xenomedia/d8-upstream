@d8 @api
Feature: DrupalContext
  In order to prove the Drupal context is working properly for Drupal 8
  As a developer
  I need to use the step definitions of this context

  Scenario: Create and log in as a user
    Given I am logged in as a user with the "authenticated user" role
    When I click "My account"
    Then I should see the text "Member for"

  Scenario: Target links within table rows
    Given I am logged in as a user with the "administrator" role
    When I am at "admin/structure/types"
    And I click "Manage fields" in the "Article" row
    Then I should be on "admin/structure/types/manage/article/fields"
    And I should see the link "Add field"

  Scenario: Create users with roles
    Given users:
    | name     | mail             | roles          |
    | Joe User | joe@example.com  | Administrator  |
    | Jane Doe | jane@example.com |                |
    And I am logged in as a user with the "administrator" role
    When I visit "admin/people"
    Then I should see the text "Administrator" in the "Joe User" row

  Scenario: Find a heading in a region
    Given I am not logged in
    When I am on the homepage
    Then I should see the heading "Search" in the "left sidebar" region
