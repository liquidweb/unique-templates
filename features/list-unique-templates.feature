Feature: Test that unique templates are identified.

  Scenario: Command can filter returned fields.
    Given a WP install
    And an active theme with the given templates:
      """
      index.php
      """

    When I run `wp theme unique-templates --fields=all`
    Then STDOUT should be a table containing rows:
      | name | filename | url |

    When I run `wp theme unique-templates --fields=name`
    Then STDOUT should be a table containing rows:
      | name |

    When I run `wp theme unique-templates --fields=filename`
    Then STDOUT should be a table containing rows:
      | filename |

    When I run `wp theme unique-templates --fields=url`
    Then STDOUT should be a table containing rows:
      | url |

    When I run `wp theme unique-templates --fields=name,filename`
    Then STDOUT should be a table containing rows:
      | name | filename |

    When I run `wp theme unique-templates --fields=filename,name`
    Then STDOUT should be a table containing rows:
      | filename | name |

  Scenario: Command can find standard templates.
    Given a WP install
    And an active theme with the given templates:
      """
      index.php
      page.php
      search.php
      """

    When I run `wp theme unique-templates --fields=filename`
    Then STDOUT should contain:
      """
      index.php
      page.php
      search.php
      """

  Scenario: Command does not report other files.
    Given a WP install
    And an active theme with the given templates:
      """
      index.php
      functions.php
      some-other-file.php
      """

    When I run `wp theme unique-templates --fields=filename`
    Then STDOUT should contain:
      """
      index.php
      """
    And STDOUT should not contain:
      """
      some-other-file.php
      """

  Scenario: Command can find named templates:
    Given a WP install
    And an active theme with the given templates:
      """
      index.php
      """
    And the given named templates:
      """
      my-template.php
      templates/another-template.php
      """

    When I run `wp theme unique-templates --fields=filename`
    Then STDOUT should contain:
      """
      index.php
      my-template.php
      templates/another-template.php
      """

  Scenario: Command respects child themes
    Given a WP install
    And an active theme with the given templates:
      """
      index.php
      page.php
      """
    And a child theme with the given files:
      """
      tag.php
      """

    When I run `wp theme unique-templates --fields=filename`
    Then STDOUT should contain:
      """
      index.php
      page.php
      tag.php
      """

  Scenario: Command can order results by different columns
    Given a WP install
    And an active theme with the given templates:
      """
      archive.php
      category.php
      index.php
      page.php
      single.php
      tag.php
      """

    When I run `wp theme unique-templates --fields=filename --orderby=filename --order=asc`
    Then STDOUT should contain:
      """
      archive.php
      category.php
      index.php
      page.php
      single.php
      tag.php
      """

    When I run `wp theme unique-templates --fields=filename --orderby=filename --order=desc`
    Then STDOUT should contain:
      """
      tag.php
      single.php
      page.php
      index.php
      category.php
      archive.php
      """

