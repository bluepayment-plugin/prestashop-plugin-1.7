<?php

declare(strict_types=1);

/*
 * php-scoper build configuration for bluepayment.
 *
 * Prefixes third-party libraries this module bundles as dev-only sources so
 * they cannot collide ("Cannot redeclare class ...") with copies of the same
 * libraries bundled unprefixed by other installed modules (e.g. ps_checkout,
 * ps_facebook, psxmarketingwithgoogle, ps_metrics all bundle
 * segmentio/analytics-php unprefixed).
 *
 * The prefix "BluePaymentAddons" is deliberately distinct from this module's
 * own "BluePayment" src namespace (mirrors the "PaypalAddons" convention used
 * by modules/paypal for the same purpose).
 *
 * IMPORTANT: this must be run (via `composer run scope-vendor`) and its
 * output under vendor/prefixed/ committed BEFORE packaging a release ---
 * vendor-bin/ (the isolated php-scoper toolset installed via
 * bamarni/composer-bin-plugin) is dev-tooling only and is not shipped.
 *
 * Note: the scope-vendor script runs php-scoper with
 * `-d error_reporting="E_ALL & ~E_DEPRECATED"`. segmentio/analytics-php's
 * legacy source uses the "${var}" string interpolation syntax deprecated in
 * PHP 8.2+; php-scoper's own bootstrap turns warnings/deprecations raised
 * while loading scoped sources into fatal ErrorExceptions, so without that
 * flag the build aborts on the host's PHP 8.x even though nothing here
 * targets a PHP 8-only runtime.
 */

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = Isolated\Symfony\Component\Finder\Finder::class;

return [
    // Fixed prefix so the resulting namespace is predictable
    // (BluePaymentAddons\...) rather than a random per-build value.
    'prefix' => 'BluePaymentAddons',

    // Overridden by the --output-dir CLI option (see the scope-vendor
    // composer script), left null here so the config stays reusable.
    'output-dir' => null,

    'finders' => [
        // prestashop/module-lib-mbo-installer: PSR-4 "Prestashop\ModuleLibMboInstaller\" => src/
        // No runtime dependencies of its own (composer.json require is php/ext-json only).
        $finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\.md|.*\.dist|.*\.lock|Makefile|composer\.json|phpunit\.xml.*|phpstan.*\.neon.*/')
            ->exclude([
                'test',
                'tests',
                'Tests',
                'doc',
                'docs',
            ])
            ->in(__DIR__ . '/vendor-bin/php-scoper/vendor/prestashop/module-lib-mbo-installer'),

        // segmentio/analytics-php: legacy global-namespace classes (Segment, Segment_Client, ...)
        // loaded via a plain autoload "files" entry. No runtime dependencies of its own
        // (composer.json require is php only).
        $finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE.*|.*\.md|.*\.dist|.*\.lock|.*\.yml|Makefile|composer\.json|phpunit\.xml.*|phpcs\.xml.*|\.buildscript|send\.php/')
            ->exclude([
                'test',
                'tests',
                'Tests',
                'bin',
                '.circleci',
                '.github',
            ])
            ->in(__DIR__ . '/vendor-bin/php-scoper/vendor/segmentio/analytics-php'),
    ],

    'exclude-files' => [],

    // Use the host/runtime PHP version understanding so scoping stays
    // consistent with the rest of the build tooling.
    'php-version' => null,

    // segmentio/analytics-php's Segment_Client::__construct() picks its
    // consumer implementation via a plain string=>string array
    // ("lib_curl" => "Segment_Consumer_LibCurl", ...) and instantiates it
    // with `new $Consumer(...)`. php-scoper's static rewriting only touches
    // literal class-reference AST nodes (use/new/instanceof/type-hints); it
    // does not rewrite class names sitting in plain string literals. The
    // actual Segment_Consumer_* classes DO get renamed to
    // BluePaymentAddons\Segment_Consumer_*, but these array values stay as
    // bare unprefixed strings, so `new $Consumer(...)` resolves against the
    // global namespace and always throws "Class ... not found" regardless
    // of the enclosing `namespace BluePaymentAddons;` block. Patch the
    // string literals in that one file to match the real (prefixed) class
    // names.
    'patchers' => [
        static function (string $filePath, string $prefix, string $contents): string {
            if (false === strpos($filePath, 'segmentio/analytics-php/lib/Segment/Client.php')) {
                return $contents;
            }

            return str_replace(
                [
                    '"Segment_Consumer_Socket"',
                    '"Segment_Consumer_File"',
                    '"Segment_Consumer_ForkCurl"',
                    '"Segment_Consumer_LibCurl"',
                ],
                [
                    '"' . $prefix . '\\\\Segment_Consumer_Socket"',
                    '"' . $prefix . '\\\\Segment_Consumer_File"',
                    '"' . $prefix . '\\\\Segment_Consumer_ForkCurl"',
                    '"' . $prefix . '\\\\Segment_Consumer_LibCurl"',
                ],
                $contents,
            );
        },
    ],

    // prestashop/module-lib-mbo-installer's own code references real
    // PrestaShop/Symfony core symbols that exist unprefixed at runtime inside
    // any real PrestaShop install (globals such as $kernel/Context/Db/Shop,
    // and PrestaShop's own Symfony-based service container). These must be
    // left untouched or the library ends up looking for classes
    // (e.g. BluePaymentAddons\DbCore) that will never exist. Confirmed
    // against modules/paypal/vendor/prefixed/prestashop/module-lib-mbo-installer/
    // (a known-good, already-shipped scoped build of the same library), which
    // leaves every one of these exact symbols bare/unprefixed too.
    //
    // Plain (non-regex) entries here match case-insensitively via substring
    // containment on the symbol's namespace (see
    // Humbug\PhpScoper\Symbol\NamespaceRegistry::isRegisteredNamespace()),
    // so 'PrestaShop\PrestaShop' also covers
    // PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder and
    // PrestaShop\PrestaShop\Core\Module\ModuleManager without listing each
    // one, and 'Symfony' covers every Symfony\Component\... class the
    // library type-hints against (DependencyInjection\ContainerInterface,
    // Routing\Router, ...).
    'exclude-namespaces' => [
        'PrestaShop\PrestaShop',
        'Symfony',
    ],
    // Exact (case-insensitive) global-namespace class names left bare for
    // the same reason as above.
    'exclude-classes' => [
        'Module',
        'ModuleCore',
        'AppKernel',
        'ContextCore',
        'DbCore',
        'Shop',
    ],
    'exclude-functions' => [],
    'exclude-constants' => [],

    // Both libraries must end up fully namespaced under BluePaymentAddons\...
    // (segmentio/analytics-php in particular is legacy global-namespace code:
    // without this, its classes would be "exposed" i.e. left global/unprefixed,
    // which defeats the whole point of scoping it).
    'expose-global-constants' => false,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
