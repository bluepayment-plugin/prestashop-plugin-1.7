<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb4865d03bfe66f9224ec9cc1d9636384
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Dotenv\\' => 25,
        ),
        'B' => 
        array (
            'BluePayment\\Tests\\' => 18,
            'BluePayment\\' => 12,
            'BlueMedia\\ProductFeed\\' => 22,
            'BlueMedia\\OnlinePayments\\' => 25,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Dotenv\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/dotenv',
        ),
        'BluePayment\\Tests\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests/php',
        ),
        'BluePayment\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
            1 => __DIR__ . '/../..' . '/src',
        ),
        'BlueMedia\\ProductFeed\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/ProductFeed',
            1 => __DIR__ . '/../..' . '/src/ProductFeed',
        ),
        'BlueMedia\\OnlinePayments\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/OnlinePayments',
            1 => __DIR__ . '/../..' . '/src/OnlinePayments',
        ),
    );

    public static $classMap = array (
        'BlueMedia\\OnlinePayments\\Action\\ITN\\Transformer' => __DIR__ . '/../..' . '/src/OnlinePayments/Action/ITN/Transformer.php',
        'BlueMedia\\OnlinePayments\\Action\\PaywayList\\Transformer' => __DIR__ . '/../..' . '/src/OnlinePayments/Action/PaywayList/Transformer.php',
        'BlueMedia\\OnlinePayments\\Gateway' => __DIR__ . '/../..' . '/src/OnlinePayments/Gateway.php',
        'BlueMedia\\OnlinePayments\\HttpClient\\CurtHttpClient' => __DIR__ . '/../..' . '/src/OnlinePayments/HttpClient/CurtHttpClient.php',
        'BlueMedia\\OnlinePayments\\HttpClient\\CurtHttpResponse' => __DIR__ . '/../..' . '/src/OnlinePayments/HttpClient/CurtHttpResponse.php',
        'BlueMedia\\OnlinePayments\\Model\\AbstractModel' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/AbstractModel.php',
        'BlueMedia\\OnlinePayments\\Model\\Gateway' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/Gateway.php',
        'BlueMedia\\OnlinePayments\\Model\\ItnIn' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/ItnIn.php',
        'BlueMedia\\OnlinePayments\\Model\\PaywayList' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/PaywayList.php',
        'BlueMedia\\OnlinePayments\\Model\\TransactionBackground' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/TransactionBackground.php',
        'BlueMedia\\OnlinePayments\\Model\\TransactionInit' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/TransactionInit.php',
        'BlueMedia\\OnlinePayments\\Model\\TransactionStandard' => __DIR__ . '/../..' . '/src/OnlinePayments/Model/TransactionStandard.php',
        'BlueMedia\\OnlinePayments\\Util\\EnvironmentRequirements' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/EnvironmentRequirements.php',
        'BlueMedia\\OnlinePayments\\Util\\Formatter' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/Formatter.php',
        'BlueMedia\\OnlinePayments\\Util\\HttpClient' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/HttpClient.php',
        'BlueMedia\\OnlinePayments\\Util\\Logger' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/Logger.php',
        'BlueMedia\\OnlinePayments\\Util\\Sorter' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/Sorter.php',
        'BlueMedia\\OnlinePayments\\Util\\Translations' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/Translations.php',
        'BlueMedia\\OnlinePayments\\Util\\Validator' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/Validator.php',
        'BlueMedia\\OnlinePayments\\Util\\XMLParser' => __DIR__ . '/../..' . '/src/OnlinePayments/Util/XMLParser.php',
        'BlueMedia\\ProductFeed\\Configuration\\FeedConfiguration' => __DIR__ . '/../..' . '/src/ProductFeed/Configuration/FeedConfiguration.php',
        'BlueMedia\\ProductFeed\\Configuration\\FileConfiguration' => __DIR__ . '/../..' . '/src/ProductFeed/Configuration/FileConfiguration.php',
        'BlueMedia\\ProductFeed\\Configuration\\XmlDataConfiguration' => __DIR__ . '/../..' . '/src/ProductFeed/Configuration/XmlDataConfiguration.php',
        'BlueMedia\\ProductFeed\\Configuration\\XmlFeedConfiguration' => __DIR__ . '/../..' . '/src/ProductFeed/Configuration/XmlFeedConfiguration.php',
        'BlueMedia\\ProductFeed\\Creator\\SimpleXMLCreator' => __DIR__ . '/../..' . '/src/ProductFeed/Creator/SimpleXMLCreator.php',
        'BlueMedia\\ProductFeed\\DataProvider\\ProductDataProvider' => __DIR__ . '/../..' . '/src/ProductFeed/DataProvider/ProductDataProvider.php',
        'BlueMedia\\ProductFeed\\Executor\\ProductExecutor' => __DIR__ . '/../..' . '/src/ProductFeed/Executor/ProductExecutor.php',
        'BlueMedia\\ProductFeed\\Generator\\XmlGenerator' => __DIR__ . '/../..' . '/src/ProductFeed/Generator/XmlGenerator.php',
        'BlueMedia\\ProductFeed\\Menager\\FileMenager' => __DIR__ . '/../..' . '/src/ProductFeed/Menager/FileMenager.php',
        'BlueMedia\\ProductFeed\\Presenter\\ProductPresenter' => __DIR__ . '/../..' . '/src/ProductFeed/Presenter/ProductPresenter.php',
        'BlueMedia\\ProductFeed\\Remover\\FileRemover' => __DIR__ . '/../..' . '/src/ProductFeed/Remover/FileRemover.php',
        'BluePayment' => __DIR__ . '/../..' . '/bluepayment.php',
        'BluePayment\\Adapter\\ConfigurationAdapter' => __DIR__ . '/../..' . '/src/Adapter/ConfigurationAdapter.php',
        'BluePayment\\Analyse\\Amplitude' => __DIR__ . '/../..' . '/src/Analyse/Amplitude.php',
        'BluePayment\\Analyse\\AnalyticsTracking' => __DIR__ . '/../..' . '/src/Analyse/AnalyticsTracking.php',
        'BluePayment\\Api\\BlueAPI' => __DIR__ . '/../..' . '/src/Api/BlueAPI.php',
        'BluePayment\\Api\\BlueGateway' => __DIR__ . '/../..' . '/src/Api/BlueGateway.php',
        'BluePayment\\Api\\BlueGatewayChannels' => __DIR__ . '/../..' . '/src/Api/BlueGatewayChannels.php',
        'BluePayment\\Api\\BlueGatewayTransfers' => __DIR__ . '/../..' . '/src/Api/BlueGatewayTransfers.php',
        'BluePayment\\Api\\GatewayInterface' => __DIR__ . '/../..' . '/src/Api/GatewayInterface.php',
        'BluePayment\\Config\\Config' => __DIR__ . '/../..' . '/src/Config/Config.php',
        'BluePayment\\Config\\ConfigBanner' => __DIR__ . '/../..' . '/src/Config/ConfigBanner.php',
        'BluePayment\\Config\\ConfigServices' => __DIR__ . '/../..' . '/src/Config/ConfigServices.php',
        'BluePayment\\Configure\\Configure' => __DIR__ . '/../..' . '/src/Configure/Configure.php',
        'BluePayment\\Hook\\AbstractHook' => __DIR__ . '/../..' . '/src/Hook/AbstractHook.php',
        'BluePayment\\Hook\\Admin' => __DIR__ . '/../..' . '/src/Hook/Admin.php',
        'BluePayment\\Hook\\Design' => __DIR__ . '/../..' . '/src/Hook/Design.php',
        'BluePayment\\Hook\\HookDispatcher' => __DIR__ . '/../..' . '/src/Hook/HookDispatcher.php',
        'BluePayment\\Hook\\Payment' => __DIR__ . '/../..' . '/src/Hook/Payment.php',
        'BluePayment\\Install\\Installer' => __DIR__ . '/../..' . '/src/Install/Installer.php',
        'BluePayment\\Service\\FactoryPaymentMethods' => __DIR__ . '/../..' . '/src/Service/FactoryPaymentMethods.php',
        'BluePayment\\Service\\Gateway' => __DIR__ . '/../..' . '/src/Service/Gateway.php',
        'BluePayment\\Service\\PaymentMethods\\AliorInstallment' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/AliorInstallment.php',
        'BluePayment\\Service\\PaymentMethods\\Blik' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/Blik.php',
        'BluePayment\\Service\\PaymentMethods\\BlikLater' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/BlikLater.php',
        'BluePayment\\Service\\PaymentMethods\\Card' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/Card.php',
        'BluePayment\\Service\\PaymentMethods\\GatewayType' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/GatewayType.php',
        'BluePayment\\Service\\PaymentMethods\\InternetTransfer' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/InternetTransfer.php',
        'BluePayment\\Service\\PaymentMethods\\MainGateway' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/MainGateway.php',
        'BluePayment\\Service\\PaymentMethods\\PayPo' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/PayPo.php',
        'BluePayment\\Service\\PaymentMethods\\Spingo' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/Spingo.php',
        'BluePayment\\Service\\PaymentMethods\\VirtualWallet' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/VirtualWallet.php',
        'BluePayment\\Service\\PaymentMethods\\VisaMobile' => __DIR__ . '/../..' . '/src/Service/PaymentMethods/VisaMobile.php',
        'BluePayment\\Service\\Refund' => __DIR__ . '/../..' . '/src/Service/Refund.php',
        'BluePayment\\Service\\Transactions' => __DIR__ . '/../..' . '/src/Service/Transactions.php',
        'BluePayment\\Statuses\\CustomStatus' => __DIR__ . '/../..' . '/src/Statuses/CustomStatus.php',
        'BluePayment\\Statuses\\OrderStatusMessageDictionary' => __DIR__ . '/../..' . '/src/Statuses/OrderStatusMessageDictionary.php',
        'BluePayment\\Until\\AdminHelper' => __DIR__ . '/../..' . '/src/Until/AdminHelper.php',
        'BluePayment\\Until\\AnaliticsHelper' => __DIR__ . '/../..' . '/src/Until/AnaliticsHelper.php',
        'BluePayment\\Until\\Helper' => __DIR__ . '/../..' . '/src/Until/Helper.php',
        'Symfony\\Component\\Dotenv\\Dotenv' => __DIR__ . '/..' . '/symfony/dotenv/Dotenv.php',
        'Symfony\\Component\\Dotenv\\Exception\\ExceptionInterface' => __DIR__ . '/..' . '/symfony/dotenv/Exception/ExceptionInterface.php',
        'Symfony\\Component\\Dotenv\\Exception\\FormatException' => __DIR__ . '/..' . '/symfony/dotenv/Exception/FormatException.php',
        'Symfony\\Component\\Dotenv\\Exception\\FormatExceptionContext' => __DIR__ . '/..' . '/symfony/dotenv/Exception/FormatExceptionContext.php',
        'Symfony\\Component\\Dotenv\\Exception\\PathException' => __DIR__ . '/..' . '/symfony/dotenv/Exception/PathException.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb4865d03bfe66f9224ec9cc1d9636384::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb4865d03bfe66f9224ec9cc1d9636384::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitb4865d03bfe66f9224ec9cc1d9636384::$classMap;

        }, null, ClassLoader::class);
    }
}
