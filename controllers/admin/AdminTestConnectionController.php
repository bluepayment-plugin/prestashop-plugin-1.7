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
if (!defined('_PS_VERSION_')) {
    exit;
}

use BluePayment\Test\Error\Handler\ErrorHandler;
use BluePayment\Test\Exception\TestException;
use BluePayment\Test\Executor\Interfaces\TestExecutorInterface;
use BluePayment\Test\Factory\TestExecutorFactory;
use BluePayment\Test\Logger\LogFileManager;

/**
 * @method string l(string $string, string $class = null, bool $addslashes = false, bool $htmlentities = true)
 */
class AdminTestConnectionController extends ModuleAdminController
{
    /** @var BluePayment */
    public $module;

    public $bootstrap = true;

    private const ACTION_GET_TEST_STEPS = 'getTestSteps';
    private const ACTION_EXECUTE_TEST_STEP = 'execute';
    private const ACTION_DOWNLOAD_TEST_LOGS = 'downloadLogs';

    /** @var TestExecutorFactory */
    private $factory;

    /** @var ErrorHandler */
    private $errorHandler;

    public function init(): void
    {
        parent::init();
        $this->factory = new TestExecutorFactory($this->module, $this->context);

        $this->errorHandler = new ErrorHandler('connection');
        $this->errorHandler->register();
    }

    public function initContent(): void
    {
        parent::initContent();
        $this->ajax = true;
    }

    public function postProcess(): void
    {
        if (Tools::isSubmit('ajax')) {
            $testType = Tools::getValue('test_type', 'connection');
            $testStep = Tools::getValue('test_step', '');

            try {
                $action = Tools::getValue('action', '');

                switch ($action) {
                    case self::ACTION_GET_TEST_STEPS:
                        $this->processGetTestSteps($testType);
                        break;
                    case self::ACTION_EXECUTE_TEST_STEP:
                        $this->processExecuteTestStep($testType, $testStep);
                        break;
                    case self::ACTION_DOWNLOAD_TEST_LOGS:
                        $this->processDownloadTestLogs($testType);
                        break;
                    default:
                        $this->ajaxError($this->l('Unknown action'), $testType, null);
                }
            } catch (TestException $e) {
                $this->ajaxTestException($e, $testType, $testStep);
            } catch (\Throwable $e) {
                $this->errorHandler->logException($e);
                $this->ajaxError($this->l('An unexpected error occurred during the test. Please check the logs for details.'), $testType, $testStep);
            }
        }

        parent::postProcess();
    }

    private function getTestExecutor(string $testType): TestExecutorInterface
    {
        try {
            return $this->factory->createTestExecutor($testType);
        } catch (Exception $e) {
            throw new Exception($this->l('Unknown test type') . ': ' . $testType);
        }
    }

    private function processGetTestSteps(string $testType): void
    {
        try {
            $testExecutor = $this->getTestExecutor($testType);
            $testExecutor->startNewTestSession();
            $steps = $testExecutor->getAvailableTestSteps();

            $this->ajaxResponse([
                'status' => 'success',
                'success' => true,
                'steps' => $steps,
                'test_type' => $testType,
            ]);
        } catch (Exception $e) {
            $this->ajaxError($this->l('Error getting test steps') . ': ' . $e->getMessage(), $testType, null);
        }
    }

    /**
     * @param string $testType
     * @param string $testStep
     *
     * @return void
     */
    private function processExecuteTestStep(string $testType, string $testStep): void
    {
        if (empty($testStep)) {
            $this->ajaxError($this->l('Test step not specified'), $testType, null);

            return;
        }

        try {
            $testExecutor = $this->getTestExecutor($testType);
            $result = $this->executeTestStep($testExecutor, $testType, $testStep);

            $this->ajaxResponse($result);
        } catch (TestException $e) {
            $this->handleTestException($testType, $testStep, $e);
        } catch (Exception $e) {
            $this->handleGenericException($testType, $testStep, $e);
        }
    }

    /**
     * @param TestExecutorInterface $testExecutor
     * @param string $testType
     * @param string $testStep
     *
     * @return array
     */
    private function executeTestStep(TestExecutorInterface $testExecutor, string $testType, string $testStep): array
    {
        static $cachedSteps = [];
        if (!isset($cachedSteps[$testType])) {
            $cachedSteps[$testType] = $testExecutor->getAvailableTestSteps();
        }
        $steps = $cachedSteps[$testType];

        $stepNumber = array_search($testStep, $steps) !== false ? array_search($testStep, $steps) + 1 : 0;

        $result = $testExecutor->execute($testStep);
        $result['step'] = $testStep;
        $result['test_type'] = $testType;

        if ($stepNumber === count($steps)) {
            $status = $result['status'] === 'success' ? 'SUCCESS' : 'FAILURE';
            $statistics = [
                'total_steps' => count($steps),
                'execution_time' => isset($result['execution_time']) ? $result['execution_time'] : null,
                'status' => $result['status'],
            ];
            $testExecutor->logTestSummary($status, $statistics);
        }

        return $result;
    }

    /**
     * @param string $testType Typ testu
     * @param string $testStep Identyfikator kroku testu
     * @param TestException $e Wyjątek testu
     *
     * @return void
     */
    private function handleTestException(string $testType, string $testStep, TestException $e): void
    {
        $this->ajaxTestException($e, $testType, $testStep);
    }

    /**
     * @param string $testType Typ testu
     * @param string $testStep Identyfikator kroku testu
     * @param Exception $e Wyjątek
     *
     * @return void
     */
    private function handleGenericException(string $testType, string $testStep, Exception $e): void
    {
        $this->ajaxError($this->l('Test error') . ': ' . $e->getMessage(), $testType, $testStep);
    }

    private function ajaxResponse(array $data): void
    {
        header('Content-Type: application/json');
        exit(json_encode($data));
    }

    private function ajaxError(string $message, string $testType, ?string $testStep): void
    {
        $response = [
            'status' => 'error',
            'success' => false,
            'message' => $message,
            'test_type' => $testType,
        ];

        if ($testStep !== null) {
            $response['step'] = $testStep;
        }

        $this->ajaxResponse($response);
    }

    private function ajaxTestException(TestException $exception, string $testType, string $testStep): void
    {
        $result = $exception->toArray();
        $result['step'] = $testStep;
        $result['test_type'] = $testType;
        $result['success'] = false;

        $this->ajaxResponse($result);
    }

    /**
     * @param string $testType
     */
    private function processDownloadTestLogs(string $testType): void
    {
        try {
            $testExecutor = $this->getTestExecutor($testType);
            $logFileManager = new LogFileManager();
            $logFilePath = $logFileManager->getLogFilePath($testExecutor->getLogger());
            $logFileManager->downloadLogFile($logFilePath, $testType);
        } catch (Exception $e) {
            $this->ajaxError($this->l('Error downloading test logs') . ': ' . $e->getMessage(), $testType, null);
        }
    }
}
