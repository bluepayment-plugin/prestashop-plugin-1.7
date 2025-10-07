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

namespace BluePayment\Test\Factory;

use BluePayment\Test\Checker\Interfaces\CheckerInterface;
use BluePayment\Test\Checker\Transaction\BlikTransactionChecker;
use BluePayment\Test\Exception\TestException;
use BluePayment\Test\Processor\BlikTransactionProcessor;
use BluePayment\Test\Processor\OrderProcessor;
use BluePayment\Test\Provider\AddressProvider;
use BluePayment\Test\Provider\BlikCodeProvider;
use BluePayment\Test\Provider\CarrierProvider;
use BluePayment\Test\Provider\CartProvider;
use BluePayment\Test\Provider\CustomerProvider;
use BluePayment\Test\Provider\ProductProvider;
use BluePayment\Test\Repository\TransactionRepository;
use BluePayment\Test\Sender\BlikTransactionSender;
use BluePayment\Test\Validator\BlikTransactionValidator;

if (!defined('_PS_VERSION_')) {
    exit;
}
final class CheckerFactory
{
    /**
     * @var \Module
     */
    private $module;

    /**
     * @var \Context
     */
    private $context;

    /**
     * @var array
     */
    private $dependencies;

    public function __construct(\Module $module, \Context $context)
    {
        $this->module = $module;
        $this->context = $context;
        $this->dependencies = [];

        $this->initializeDependencies();
    }

    /**
     * @param string $className Fully qualified class name
     *
     * @return CheckerInterface
     *
     * @throws TestException If checker class doesn't exist
     */
    public function createCheckerFromClass(string $className): CheckerInterface
    {
        if (!class_exists($className)) {
            throw new TestException("Checker class not found: $className");
        }

        $dependencies = $this->getDependenciesForClass($className);

        return new $className(...$dependencies);
    }

    private function initializeDependencies(): void
    {
        $this->dependencies['module'] = $this->module;
        $this->dependencies['context'] = $this->context;
    }

    /**
     * @param string $className Nazwa klasy
     *
     * @return array Tablica zależności
     */
    private function getDependenciesForClass(string $className): array
    {
        $dependencyMap = $this->getDependencyMap();

        return $dependencyMap[$className] ?? $this->getDefaultDependencies();
    }

    /**
     * @return array
     */
    private function getDependencyMap(): array
    {
        return [
            BlikTransactionChecker::class => [
                $this->dependencies['module'],
                $this->dependencies['context'],
                $this->getOrderProcessor(),
                $this->getBlikProcessor(),
                $this->getBlikCodeProvider(),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getDefaultDependencies(): array
    {
        return [
            $this->dependencies['module'],
            $this->dependencies['context'],
        ];
    }

    /**
     * @return OrderProcessor
     */
    private function getOrderProcessor(): OrderProcessor
    {
        if (!isset($this->dependencies['orderProcessor'])) {
            $testObjectFactory = $this->getTestObjectFactory();
            $this->dependencies['orderProcessor'] = new OrderProcessor($testObjectFactory, $this->module);
        }

        return $this->dependencies['orderProcessor'];
    }

    /**
     * @return BlikTransactionProcessor
     */
    private function getBlikProcessor(): BlikTransactionProcessor
    {
        if (!isset($this->dependencies['blikProcessor'])) {
            $transactionRepository = $this->getTransactionRepository();
            $blikTransactionSender = $this->getBlikTransactionSender();
            $blikTransactionValidator = $this->getBlikTransactionValidator();

            $this->dependencies['blikProcessor'] = new BlikTransactionProcessor(
                $transactionRepository,
                $blikTransactionSender,
                $blikTransactionValidator,
                $this->dependencies['module']
            );
        }

        return $this->dependencies['blikProcessor'];
    }

    /**
     * @return BlikCodeProvider
     */
    private function getBlikCodeProvider(): BlikCodeProvider
    {
        if (!isset($this->dependencies['blikCodeProvider'])) {
            $this->dependencies['blikCodeProvider'] = new BlikCodeProvider();
        }

        return $this->dependencies['blikCodeProvider'];
    }

    /**
     * @return TestObjectFactory
     */
    private function getTestObjectFactory(): TestObjectFactory
    {
        if (!isset($this->dependencies['testObjectFactory'])) {
            $this->dependencies['testObjectFactory'] = new TestObjectFactory(
                $this->getCustomerProvider(),
                $this->getAddressProvider(),
                $this->getProductProvider(),
                $this->getCartProvider(),
                $this->getCarrierProvider()
            );
        }

        return $this->dependencies['testObjectFactory'];
    }

    /**
     * @return CustomerProvider
     */
    private function getCustomerProvider(): CustomerProvider
    {
        if (!isset($this->dependencies['customerProvider'])) {
            $this->dependencies['customerProvider'] = new CustomerProvider();
        }

        return $this->dependencies['customerProvider'];
    }

    /**
     * @return AddressProvider
     */
    private function getAddressProvider(): AddressProvider
    {
        if (!isset($this->dependencies['addressProvider'])) {
            $this->dependencies['addressProvider'] = new AddressProvider();
        }

        return $this->dependencies['addressProvider'];
    }

    /**
     * @return ProductProvider
     */
    private function getProductProvider(): ProductProvider
    {
        if (!isset($this->dependencies['productProvider'])) {
            $this->dependencies['productProvider'] = new ProductProvider();
        }

        return $this->dependencies['productProvider'];
    }

    /**
     * @return CartProvider
     */
    private function getCartProvider(): CartProvider
    {
        if (!isset($this->dependencies['cartProvider'])) {
            $this->dependencies['cartProvider'] = new CartProvider();
        }

        return $this->dependencies['cartProvider'];
    }

    /**
     * @return CarrierProvider
     */
    private function getCarrierProvider(): CarrierProvider
    {
        if (!isset($this->dependencies['carrierProvider'])) {
            $this->dependencies['carrierProvider'] = new CarrierProvider();
        }

        return $this->dependencies['carrierProvider'];
    }

    /**
     * @return TransactionRepository
     */
    private function getTransactionRepository(): TransactionRepository
    {
        if (!isset($this->dependencies['transactionRepository'])) {
            $this->dependencies['transactionRepository'] = new TransactionRepository();
        }

        return $this->dependencies['transactionRepository'];
    }

    /**
     * @return BlikTransactionSender
     */
    private function getBlikTransactionSender(): BlikTransactionSender
    {
        if (!isset($this->dependencies['blikTransactionSender'])) {
            $this->dependencies['blikTransactionSender'] = new BlikTransactionSender($this->module);
        }

        return $this->dependencies['blikTransactionSender'];
    }

    /**
     * @return BlikTransactionValidator
     */
    private function getBlikTransactionValidator(): BlikTransactionValidator
    {
        if (!isset($this->dependencies['blikTransactionValidator'])) {
            $this->dependencies['blikTransactionValidator'] = new BlikTransactionValidator($this->module);
        }

        return $this->dependencies['blikTransactionValidator'];
    }
}
