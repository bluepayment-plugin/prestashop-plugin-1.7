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

namespace BluePayment\Test\Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

class TestException extends \Exception
{
    private array $errorDetails;

    public function __construct(
        string $message,
        array $errorDetails = [],
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorDetails = $errorDetails;
    }

    public function getErrorDetails(): array
    {
        return $this->errorDetails;
    }

    public function toArray(): array
    {
        return [
            'status' => 'error',
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'details' => $this->getErrorDetails(),
        ];
    }
}
