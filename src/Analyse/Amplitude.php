<?php

/**
 * NOTICE OF LICENSE
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @author     Blue Media S.A.
 * @copyright  Since 2015 Blue Media S.A.
 * @license    https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

declare(strict_types=1);

namespace BluePayment\Analyse;

use Context;

class Amplitude
{
    private $api_key = '3df98a3ac8e0efd57fbe86f86c27b488';
    private static $instance;

    /**
     * @throws \Exception
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @throws \Exception
     * @codeCoverageIgnore
     */
    private function __clone()
    {
        throw new \InvalidArgumentException('Not supported clone');
    }

    /**
     * @throws \Exception
     * @codeCoverageIgnore
     */
    private function __wakeup()
    {
        throw new \InvalidArgumentException('Not supported wakeup');
    }

    public static function getInstance(): Amplitude
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function getUserId(): string
    {
        return md5(Context::getContext()->shop->getBaseURL(true));
    }

    public function sendEvent(array $data = [])
    {
        $data['api_key'] = $this->api_key;
        $data['events']['time'] = time();
        $data['events']['user_id'] = self::getUserId();
        $data['events']['os_name'] = 'Prestashop';
        $data['events']['os_version'] = _PS_VERSION_;

        $payload = json_encode($data);
        $post_url = 'https://api2.amplitude.com/2/httpapi';

        $ch = curl_init($post_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
