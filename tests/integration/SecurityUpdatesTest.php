<?php

declare(strict_types=1);

namespace Unish;

use Drush\Commands\pm\SecurityUpdateCommands;

/**
 * Tests "pm:security" command.
 * @group commands
 * @group pm
 */
class SecurityUpdatesTest extends UnishIntegrationTestCase
{
  /**
   * Test that insecure Drupal packages are correctly identified.
   */
    public function testInsecureDrupalPackage()
    {
        $expected_package = 'drupal/semver_example';
        $expected_version = '2.3.0';
        $this->drush(SecurityUpdateCommands::SECURITY, [], ['format' => 'json'], self::EXIT_ERROR_WITH_CLARITY);
        $this->assertStringContainsString('One or more of your dependencies has an outstanding security update.', $this->getErrorOutput());
        $this->assertStringContainsString("$expected_package", $this->getErrorOutput());
        $security_advisories = $this->getOutputFromJSON();
        $this->assertArrayHasKey($expected_package, $security_advisories);
        $this->assertEquals($expected_package, $security_advisories[$expected_package]['name']);
        $this->assertEquals($expected_version, $security_advisories[$expected_package]['version']);

        // If our SUT is 9.2.8, then we should find a security update for Drupal core too.
        if (\Drupal::VERSION != '9.2.8') {
            $this->markTestSkipped("We only test for drupal/core security updates if the SUT is on Drupal 9.2.8");
        }
        $this->assertStringContainsString("Try running: composer require drupal/core", $this->getErrorOutput());
        $this->assertArrayHasKey('drupal/core', $security_advisories);
        $this->assertEquals('drupal/core', $security_advisories['drupal/core']['name']);
        $this->assertEquals('9.2.8', $security_advisories['drupal/core']['version']);
    }

    /**
     * Test that dev modules are correctly excluded.
     */
    public function testNoInsecureProductionDrupalPackage()
    {
        $this->drush(SecurityUpdateCommands::SECURITY, [], ['format' => 'json', 'no-dev' => true]);
        $this->assertStringContainsString('There are no outstanding security updates for Drupal projects', $this->getErrorOutput());
    }
}
