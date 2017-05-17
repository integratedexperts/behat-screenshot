<?php

/**
 * @file
 * Behat context to enable Screenshot support in tests.
 */

namespace IntegratedExperts\BehatScreenshot;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\MinkExtension\Context\RawMinkContext;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class ScreenshotContext.
 */
class ScreenshotContext extends RawMinkContext implements SnippetAcceptingContext
{
    /**
     * Screenshot step filename.
     *
     * @var string
     */
    protected $stepFile;

    /**
     * Directory where screenshots are stored.
     *
     * @var string
     */
    protected $dir;

    /**
     * Flag to create a screenshot when test fails.
     *
     * @var bool
     */
    protected $onFail;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the context constructor through
     * behat.yml.
     *
     * @param array $parameters Get parameters for construct test.
     */
    public function __construct($parameters = [])
    {
        $this->dir = isset($parameters['dir']) ? $parameters['dir'] : __DIR__.'/screenshot';
        $this->onFail = isset($parameters['fail']) ? $parameters['fail'] : true;
    }

    /**
     * Init function before tests run.
     *
     * @param BeforeSuiteScope $scope
     *
     * @BeforeSuite
     */
    public static function beforeSuitInit(BeforeSuiteScope $scope)
    {
        $contextSettings = [
            'dir' => getcwd().'/screenshot',
            'purge' => false,
        ];

        if (getenv('BEHAT_SCREENSHOT_PURGE')) {
            $purge = (bool) getenv('BEHAT_SCREENSHOT_PURGE');
        } else {
            foreach ($scope->getSuite()->getSetting('contexts') as $context) {
                if (isset($context['IntegratedExperts\BehatScreenshot\ScreenshotContext'][0])) {
                    $contextSettings = $context['IntegratedExperts\BehatScreenshot\ScreenshotContext'][0] + $contextSettings;
                    break;
                }
            }

            $purge = $contextSettings['purge'];
        }

        if ($purge) {
            self::purgeDir($contextSettings['dir']);
        }
    }

    /**
     * Init values required for snapshots.
     *
     * @param BeforeScenarioScope $scope Scenario scope.
     *
     * @BeforeScenario
     */
    public function beforeScenarioInit(BeforeScenarioScope $scope)
    {
        if ($scope->getScenario()->hasTag('javascript')) {
            if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
                $this->getSession()->resizeWindow(1440, 900, 'current');
            }
        }
    }

    /**
     * Init values required for snapshot.
     *
     * @param BeforeStepScope $scope
     *
     * @BeforeStep
     */
    public function beforeStepInit(BeforeStepScope $scope)
    {
        $this->stepFile = $this->screenshotScope->getFeature()->getFile();
    }

    /**
     * After scope event handler to print last response on error.
     *
     * @param AfterStepScope $event After scope event.
     *
     * @AfterStep
     */
    public function printLastResponseOnError(AfterStepScope $event)
    {
        if ($this->onFail && !$event->getTestResult()->isPassed()) {
            $this->saveDebugScreenshot();
        }
    }

    /**
     * Save debug screenshot.
     *
     * Handles different driver types.
     *
     * @When /^(?:|I\s)save screenshot$/
     */
    public function saveDebugScreenshot()
    {
        $this->prepareDir();

        $driver = $this->getSession()->getDriver();
        if ($driver instanceof GoutteDriver) {
            // Goutte is a pure PHP browser, so the only 'screenshot' we can
            // save is actual HTML of the page.
            $filename = $this->makeFileName('html');
            // Try to get a response from the visited page, if there is any
            // loaded content at all.
            try {
                $html = $this->getSession()->getDriver()->getContent();
                $this->writeFile($filename, $html);
            } catch (Exception $e) {
            }
        }

        // Selenium driver covers Selenium and PhantomJS.
        if ($driver instanceof Selenium2Driver) {
            $filename = $this->makeFileName('png');
            $this->saveScreenshot($filename, $this->dir);
        }
    }


    /**
     * Make screenshot filename.
     *
     * Format: micro.seconds_title_of_scenario_trimmed.ext.
     *
     * @param string $ext File extension without dot.
     *
     * @return string
     *   Unique file name.
     */
    protected function makeFileName($ext)
    {
        $fileName = basename($this->stepFile);
        $stepLine = $this->screenshotScope->getStep()->getLine();

        return sprintf('%s.%s_[%s].%s', microtime(), $fileName, $stepLine, $ext);
    }


    /**
     * Prepare directory for write new screenshot.
     */
    protected function prepareDir()
    {
        $fs = new Filesystem();
        $fs->mkdir($this->dir, 0755);
    }

    /**
     * Remove directory with previous screenshots.
     *
     * @param string $dir Screenshot directory name.
     */
    protected static function purgeDir($dir)
    {
        $fs = new Filesystem();
        $finder = new Finder();
        if ($fs->exists($dir)) {
            $fs->remove($finder->files()->in($dir));
        }
    }


    /**
     * Write data into file.
     *
     * @param string $filename Name for write file.
     * @param string $data Data for write ito file.
     */
    protected function writeFile($filename, $data)
    {
        file_put_contents($this->dir.DIRECTORY_SEPARATOR.$filename, $data);
    }
}
