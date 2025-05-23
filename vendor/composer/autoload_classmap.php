<?php

// autoload_classmap.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'BlueMedia\\OnlinePayments\\Action\\ITN\\Transformer' => $baseDir . '/src/OnlinePayments/Action/ITN/Transformer.php',
    'BlueMedia\\OnlinePayments\\Action\\PaywayList\\Transformer' => $baseDir . '/src/OnlinePayments/Action/PaywayList/Transformer.php',
    'BlueMedia\\OnlinePayments\\Gateway' => $baseDir . '/src/OnlinePayments/Gateway.php',
    'BlueMedia\\OnlinePayments\\HttpClient\\CurtHttpClient' => $baseDir . '/src/OnlinePayments/HttpClient/CurtHttpClient.php',
    'BlueMedia\\OnlinePayments\\HttpClient\\CurtHttpResponse' => $baseDir . '/src/OnlinePayments/HttpClient/CurtHttpResponse.php',
    'BlueMedia\\OnlinePayments\\Model\\AbstractModel' => $baseDir . '/src/OnlinePayments/Model/AbstractModel.php',
    'BlueMedia\\OnlinePayments\\Model\\Gateway' => $baseDir . '/src/OnlinePayments/Model/Gateway.php',
    'BlueMedia\\OnlinePayments\\Model\\ItnIn' => $baseDir . '/src/OnlinePayments/Model/ItnIn.php',
    'BlueMedia\\OnlinePayments\\Model\\PaywayList' => $baseDir . '/src/OnlinePayments/Model/PaywayList.php',
    'BlueMedia\\OnlinePayments\\Model\\TransactionBackground' => $baseDir . '/src/OnlinePayments/Model/TransactionBackground.php',
    'BlueMedia\\OnlinePayments\\Model\\TransactionInit' => $baseDir . '/src/OnlinePayments/Model/TransactionInit.php',
    'BlueMedia\\OnlinePayments\\Model\\TransactionStandard' => $baseDir . '/src/OnlinePayments/Model/TransactionStandard.php',
    'BlueMedia\\OnlinePayments\\Util\\EnvironmentRequirements' => $baseDir . '/src/OnlinePayments/Util/EnvironmentRequirements.php',
    'BlueMedia\\OnlinePayments\\Util\\Formatter' => $baseDir . '/src/OnlinePayments/Util/Formatter.php',
    'BlueMedia\\OnlinePayments\\Util\\HttpClient' => $baseDir . '/src/OnlinePayments/Util/HttpClient.php',
    'BlueMedia\\OnlinePayments\\Util\\Logger' => $baseDir . '/src/OnlinePayments/Util/Logger.php',
    'BlueMedia\\OnlinePayments\\Util\\Sorter' => $baseDir . '/src/OnlinePayments/Util/Sorter.php',
    'BlueMedia\\OnlinePayments\\Util\\Translations' => $baseDir . '/src/OnlinePayments/Util/Translations.php',
    'BlueMedia\\OnlinePayments\\Util\\Validator' => $baseDir . '/src/OnlinePayments/Util/Validator.php',
    'BlueMedia\\OnlinePayments\\Util\\XMLParser' => $baseDir . '/src/OnlinePayments/Util/XMLParser.php',
    'BlueMedia\\ProductFeed\\Configuration\\FeedConfiguration' => $baseDir . '/src/ProductFeed/Configuration/FeedConfiguration.php',
    'BlueMedia\\ProductFeed\\Configuration\\FileConfiguration' => $baseDir . '/src/ProductFeed/Configuration/FileConfiguration.php',
    'BlueMedia\\ProductFeed\\Configuration\\XmlDataConfiguration' => $baseDir . '/src/ProductFeed/Configuration/XmlDataConfiguration.php',
    'BlueMedia\\ProductFeed\\Configuration\\XmlFeedConfiguration' => $baseDir . '/src/ProductFeed/Configuration/XmlFeedConfiguration.php',
    'BlueMedia\\ProductFeed\\Creator\\SimpleXMLCreator' => $baseDir . '/src/ProductFeed/Creator/SimpleXMLCreator.php',
    'BlueMedia\\ProductFeed\\DataProvider\\ProductDataProvider' => $baseDir . '/src/ProductFeed/DataProvider/ProductDataProvider.php',
    'BlueMedia\\ProductFeed\\Executor\\ProductExecutor' => $baseDir . '/src/ProductFeed/Executor/ProductExecutor.php',
    'BlueMedia\\ProductFeed\\Generator\\XmlGenerator' => $baseDir . '/src/ProductFeed/Generator/XmlGenerator.php',
    'BlueMedia\\ProductFeed\\Menager\\FileMenager' => $baseDir . '/src/ProductFeed/Menager/FileMenager.php',
    'BlueMedia\\ProductFeed\\Presenter\\ProductPresenter' => $baseDir . '/src/ProductFeed/Presenter/ProductPresenter.php',
    'BlueMedia\\ProductFeed\\Remover\\FileRemover' => $baseDir . '/src/ProductFeed/Remover/FileRemover.php',
    'BluePayment' => $baseDir . '/bluepayment.php',
    'BluePayment\\Adapter\\ConfigurationAdapter' => $baseDir . '/src/Adapter/ConfigurationAdapter.php',
    'BluePayment\\Analyse\\Amplitude' => $baseDir . '/src/Analyse/Amplitude.php',
    'BluePayment\\Analyse\\AnalyticsTracking' => $baseDir . '/src/Analyse/AnalyticsTracking.php',
    'BluePayment\\Api\\BlueAPI' => $baseDir . '/src/Api/BlueAPI.php',
    'BluePayment\\Api\\BlueGateway' => $baseDir . '/src/Api/BlueGateway.php',
    'BluePayment\\Api\\BlueGatewayChannels' => $baseDir . '/src/Api/BlueGatewayChannels.php',
    'BluePayment\\Api\\BlueGatewayTransfers' => $baseDir . '/src/Api/BlueGatewayTransfers.php',
    'BluePayment\\Api\\GatewayInterface' => $baseDir . '/src/Api/GatewayInterface.php',
    'BluePayment\\Config\\Config' => $baseDir . '/src/Config/Config.php',
    'BluePayment\\Config\\ConfigBanner' => $baseDir . '/src/Config/ConfigBanner.php',
    'BluePayment\\Config\\ConfigServices' => $baseDir . '/src/Config/ConfigServices.php',
    'BluePayment\\Configure\\Configure' => $baseDir . '/src/Configure/Configure.php',
    'BluePayment\\Hook\\AbstractHook' => $baseDir . '/src/Hook/AbstractHook.php',
    'BluePayment\\Hook\\Admin' => $baseDir . '/src/Hook/Admin.php',
    'BluePayment\\Hook\\Design' => $baseDir . '/src/Hook/Design.php',
    'BluePayment\\Hook\\HookDispatcher' => $baseDir . '/src/Hook/HookDispatcher.php',
    'BluePayment\\Hook\\Payment' => $baseDir . '/src/Hook/Payment.php',
    'BluePayment\\Install\\Installer' => $baseDir . '/src/Install/Installer.php',
    'BluePayment\\Service\\FactoryPaymentMethods' => $baseDir . '/src/Service/FactoryPaymentMethods.php',
    'BluePayment\\Service\\Gateway' => $baseDir . '/src/Service/Gateway.php',
    'BluePayment\\Service\\PaymentMethods\\AliorInstallment' => $baseDir . '/src/Service/PaymentMethods/AliorInstallment.php',
    'BluePayment\\Service\\PaymentMethods\\Blik' => $baseDir . '/src/Service/PaymentMethods/Blik.php',
    'BluePayment\\Service\\PaymentMethods\\BlikLater' => $baseDir . '/src/Service/PaymentMethods/BlikLater.php',
    'BluePayment\\Service\\PaymentMethods\\Card' => $baseDir . '/src/Service/PaymentMethods/Card.php',
    'BluePayment\\Service\\PaymentMethods\\GatewayType' => $baseDir . '/src/Service/PaymentMethods/GatewayType.php',
    'BluePayment\\Service\\PaymentMethods\\InternetTransfer' => $baseDir . '/src/Service/PaymentMethods/InternetTransfer.php',
    'BluePayment\\Service\\PaymentMethods\\MainGateway' => $baseDir . '/src/Service/PaymentMethods/MainGateway.php',
    'BluePayment\\Service\\PaymentMethods\\PayPo' => $baseDir . '/src/Service/PaymentMethods/PayPo.php',
    'BluePayment\\Service\\PaymentMethods\\Spingo' => $baseDir . '/src/Service/PaymentMethods/Spingo.php',
    'BluePayment\\Service\\PaymentMethods\\VirtualWallet' => $baseDir . '/src/Service/PaymentMethods/VirtualWallet.php',
    'BluePayment\\Service\\PaymentMethods\\VisaMobile' => $baseDir . '/src/Service/PaymentMethods/VisaMobile.php',
    'BluePayment\\Service\\Refund' => $baseDir . '/src/Service/Refund.php',
    'BluePayment\\Service\\Transactions' => $baseDir . '/src/Service/Transactions.php',
    'BluePayment\\Statuses\\CustomStatus' => $baseDir . '/src/Statuses/CustomStatus.php',
    'BluePayment\\Statuses\\OrderStatusMessageDictionary' => $baseDir . '/src/Statuses/OrderStatusMessageDictionary.php',
    'BluePayment\\Until\\AdminHelper' => $baseDir . '/src/Until/AdminHelper.php',
    'BluePayment\\Until\\AnaliticsHelper' => $baseDir . '/src/Until/AnaliticsHelper.php',
    'BluePayment\\Until\\Helper' => $baseDir . '/src/Until/Helper.php',
    'Symfony\\Component\\Dotenv\\Dotenv' => $vendorDir . '/symfony/dotenv/Dotenv.php',
    'Symfony\\Component\\Dotenv\\Exception\\ExceptionInterface' => $vendorDir . '/symfony/dotenv/Exception/ExceptionInterface.php',
    'Symfony\\Component\\Dotenv\\Exception\\FormatException' => $vendorDir . '/symfony/dotenv/Exception/FormatException.php',
    'Symfony\\Component\\Dotenv\\Exception\\FormatExceptionContext' => $vendorDir . '/symfony/dotenv/Exception/FormatExceptionContext.php',
    'Symfony\\Component\\Dotenv\\Exception\\PathException' => $vendorDir . '/symfony/dotenv/Exception/PathException.php',
);
