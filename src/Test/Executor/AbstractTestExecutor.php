<?php
/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Autopay S.A.
 * @copyright  Since 2015 Autopay S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Test\Executor;

use BluePayment\Test\Checker\Common\ConfigurationChecker;
use BluePayment\Test\Checker\Common\DatabaseEnvironmentChecker;
use BluePayment\Test\Checker\Common\HttpsChecker;
use BluePayment\Test\Checker\Common\InternetConnectionChecker;
use BluePayment\Test\Checker\Common\LogPermissionsChecker;
use BluePayment\Test\Checker\Common\ModuleVersionChecker;
use BluePayment\Test\Checker\Common\PhpEnvironmentChecker;
use BluePayment\Test\Checker\Common\PrestaShopVersionChecker;
use BluePayment\Test\Exception\TestException;
use BluePayment\Test\Executor\Interfaces\TestExecutorInterface;
use BluePayment\Test\Factory\CheckerFactory;
use BluePayment\Test\Logger\Interfaces\TestLoggerInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

abstract class AbstractTestExecutor implements TestExecutorInterface
{
    /**
     * @var \Module
     */
    protected $module;

    /**
     * @var \Context
     */
    protected $context;

    /**
     * @var array
     */
    protected $checkerClasses;

    /**
     * @var TestLoggerInterface
     */
    protected $logger;

    /**
     * @var CheckerFactory
     */
    protected $checkerFactory;

    public function __construct(\Module $module, \Context $context, TestLoggerInterface $logger)
    {
        $this->module = $module;
        $this->context = $context;
        $this->logger = $logger;
        $this->checkerClasses = $this->getCheckerClassesFromConfig();
        $this->checkerFactory = new CheckerFactory($module, $context);
    }

    /**
     * @return array
     */
    protected function getCheckerClassesFromConfig(): array
    {
        return array_merge(
            $this->getCommonCheckerClasses(),
            $this->getSpecificCheckerClasses()
        );
    }

    /**
     * Zwraca listę klas Checkerów wspólnych dla wszystkich executorów
     *
     * @return array
     */
    protected function getCommonCheckerClasses(): array
    {
        return [
            PhpEnvironmentChecker::class,
            DatabaseEnvironmentChecker::class,
            HttpsChecker::class,
            InternetConnectionChecker::class,
            LogPermissionsChecker::class,
            ModuleVersionChecker::class,
            PrestaShopVersionChecker::class,
            ConfigurationChecker::class,
        ];
    }

    /**
     * Zwraca listę klas Checkerów specyficznych dla danego executora
     *
     * @return array
     */
    abstract protected function getSpecificCheckerClasses(): array;

    /**
     * @param string $step
     *
     * @return array
     *
     * @throws TestException
     */
    public function execute(string $step): array
    {
        $this->logger->debug('Test step started', [
            'step' => $step,
        ]);

        try {
            $checkerClass = $this->findCheckerClassByStep($step);

            if (!$checkerClass) {
                $this->logger->error('Unknown test step', ['step' => $step]);
                throw new TestException('Unknown test step: ' . $step, ['step' => $step]);
            }

            $checker = $this->checkerFactory->createCheckerFromClass($checkerClass);
            $result = $checker->check();

            $logLevel = ($result['status'] === 'success') ? 'info' : 'warning';
            $this->logger->$logLevel('Test step completed', [
                'step' => $step,
                'status' => $result['status'],
                'message' => $result['message'] ?? '',
                'details' => $result['details'] ?? [],
            ]);

            return $result;
        } catch (TestException $e) {
            $this->logger->error('Test exception', [
                'step' => $step,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected exception', [
                'step' => $step,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw new TestException('Unexpected error: ' . $e->getMessage(), [], $e->getCode(), $e);
        }
    }

    public function getAvailableTestSteps(): array
    {
        $steps = [];

        foreach ($this->checkerClasses as $checkerClass) {
            $stepName = $this->getStepNameFromClass($checkerClass);
            $steps[] = $stepName;
        }

        return $steps;
    }

    public function startNewTestSession(): void
    {
        $this->logger->startNewTestSession();

        $steps = $this->getAvailableTestSteps();
        $this->logger->info('Retrieved available test steps', [
            'steps_count' => count($steps),
            'steps' => $steps,
        ]);
    }

    public function logTestStep(string $stepName, int $stepNumber): void
    {
        $this->logger->logTestStep($stepName, $stepNumber);
    }

    public function logTestSummary(string $status, array $statistics = []): void
    {
        $this->logger->logTestSummary($status, $statistics);
    }

    public function getLogger(): TestLoggerInterface
    {
        return $this->logger;
    }

    protected function findCheckerClassByStep(string $step): ?string
    {
        foreach ($this->checkerClasses as $checkerClass) {
            $stepName = $this->getStepNameFromClass($checkerClass);

            if ($stepName === $step) {
                return $checkerClass;
            }
        }

        return null;
    }

    protected function getStepNameFromClass(string $className): string
    {
        $parts = explode('\\', $className);
        $classShortName = end($parts);
        $stepName = preg_replace('/Checker$/', '', $classShortName);
        $stepName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $stepName);

        return strtolower($stepName);
    }
}
