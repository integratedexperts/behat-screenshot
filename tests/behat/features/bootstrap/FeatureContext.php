<?php

/**
 * @file
 * Feature context Behat testing.
 */

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    /**
     * @var string $screenshotDir Directory where screenshots are stored.
     */
    protected $screenshotDir;

    /**
     * FeatureContext constructor.
     */
    public function __construct($parameters)
    {
        $this->screenshotDir = isset($parameters['screenshot_dir']) ? $parameters['screenshot_dir'] : 'screenshots';
    }

    /**
     * Go to the screenshot test page.
     *
     * @Given /^(?:|I )am on (?:|the )screenshot test page$/
     * @When /^(?:|I )go to (?:|the )screenshot test page$/
     */
    public function goToScreenshotTestPage()
    {
        $this->getSession()->visit(
            'http://localhost:8888/screenshot.html'
        );
    }

    /**
     * Checks whether a file wildcard at provided path exists.
     *
     * @param string $wildcard File name with a wildcard.
     *
     * @Given /^file wildcard "([^"]*)" should exist$/
     */
    public function assertFileShouldExist($wildcard)
    {
        $wildcard = $this->screenshotDir.DIRECTORY_SEPARATOR.$wildcard;
        $matches = glob($wildcard);

        if (empty($matches)) {
            throw new \Exception(
                sprintf(
                    "Unable to find files matching wildcard '%s'",
                    $wildcard
                )
            );
        }
    }

    /**
     * Remove all files from screenshot directory.
     *
     * @Given I remove all files from screenshot directory
     */
    public function emptyScreenshotDirectory()
    {
        array_map(
            'unlink',
            glob($this->screenshotDir.DIRECTORY_SEPARATOR.'/*')
        );
    }
}
