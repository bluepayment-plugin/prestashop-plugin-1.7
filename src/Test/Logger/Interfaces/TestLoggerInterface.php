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

namespace BluePayment\Test\Logger\Interfaces;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Interfejs dla logera testów
 */
interface TestLoggerInterface
{
    /**
     * Zapisuje wiadomość z określonym poziomem
     *
     * @param string $level Poziom logowania (debug, info, warning, error)
     * @param string $message Wiadomość do zalogowania
     * @param array $context Dodatkowe dane kontekstowe
     */
    public function log(string $level, string $message, array $context = []): void;

    /**
     * Zapisuje wiadomość z poziomem DEBUG
     *
     * @param string $message Wiadomość do zalogowania
     * @param array $context Dodatkowe dane kontekstowe
     */
    public function debug(string $message, array $context = []): void;

    /**
     * Zapisuje wiadomość z poziomem INFO
     *
     * @param string $message Wiadomość do zalogowania
     * @param array $context Dodatkowe dane kontekstowe
     */
    public function info(string $message, array $context = []): void;

    /**
     * Zapisuje wiadomość z poziomem WARNING
     *
     * @param string $message Wiadomość do zalogowania
     * @param array $context Dodatkowe dane kontekstowe
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Zapisuje wiadomość z poziomem ERROR
     *
     * @param string $message Wiadomość do zalogowania
     * @param array $context Dodatkowe dane kontekstowe
     */
    public function error(string $message, array $context = []): void;

    /**
     * Zwraca ścieżkę do pliku logów
     *
     * @return string Ścieżka do pliku logów
     */
    public function getLogFilePath(): string;

    /**
     * Rozpoczyna nową sesję testową z wyraźnym oznaczeniem w logu
     */
    public function startNewTestSession(): void;

    /**
     * Loguje informację o etapie testu
     *
     * @param string $stepName Nazwa etapu testu
     * @param int $stepNumber Numer etapu testu
     */
    public function logTestStep(string $stepName, int $stepNumber): void;

    /**
     * Loguje podsumowanie testu
     *
     * @param string $status Status testu (SUCCESS/FAILURE)
     * @param array $statistics Statystyki testu
     */
    public function logTestSummary(string $status, array $statistics = []): void;
}
