<?php

namespace Unish;

/**
 * Generate makefile tests
 *
 * @group commands
 * @group make
 * @group slow
 */
class generateMakeCase extends CommandUnishTestCase {
  function testGenerateMake() {
    $sites = $this->setUpDrupal(1, TRUE);
    $major_version = UNISH_DRUPAL_MAJOR_VERSION . '.x';

    $options = array(
      'yes' => NULL,
      'pipe' => NULL,
      'root' => $this->webroot(),
      'uri' => key($sites),
      'cache' => NULL,
      'strict' => 0, // Don't validate options
    );
    $this->drush('pm-download', array('omega', 'devel'), $options);
    $this->drush('pm-enable', array('omega', 'devel'), $options);

    $makefile = UNISH_SANDBOX . '/dev.make';

    // First generate a simple makefile with no version information
    $this->drush('generate-makefile', array($makefile), array('exclude-versions' => NULL) + $options);
    $expected = <<<EOD
; This file was auto-generated by drush make
core = $major_version
api = 2

; Core
projects[] = "drupal"
; Modules
projects[] = "devel"
; Themes
projects[] = "omega"
EOD;
    $actual = trim(file_get_contents($makefile));

    $this->assertEquals($expected, $actual);

    // Download a module to a 'contrib' directory to test the subdir feature
    mkdir($this->webroot() + '/sites/all/modules/contrib');
    $this->drush('pm-download', array('libraries'), array('destination' => 'sites/all/modules/contrib') + $options);
    $this->drush('pm-enable', array('libraries'), $options);
    $this->drush('generate-makefile', array($makefile), array('exclude-versions' => NULL) + $options);
    $expected = <<<EOD
; This file was auto-generated by drush make
core = $major_version
api = 2

; Core
projects[] = "drupal"
; Modules
projects[] = "devel"
projects[libraries][subdir] = "contrib"

; Themes
projects[] = "omega"
EOD;
    $actual = trim(file_get_contents($makefile));

    $this->assertEquals($expected, $actual);

    // Generate a makefile with version numbers.
    $this->drush('generate-makefile', array($makefile), $options);
    $actual = file_get_contents($makefile);
    $this->assertContains('projects[devel][version] = "', $actual);
  }
}
