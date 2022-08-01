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

declare(strict_types = 1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class AnalyticsTracking
{

    private $tracked_id;
    private $ga_session;
    private $api_secret;

    public function __construct($tracked_id, $ga_session, $api_secret = null)
    {
        $this->tracked_id = $tracked_id;
        $this->ga_session = $ga_session;
        $this->api_secret = $api_secret;
    }

    /**
     * Handle cid _ga cookie
     *
     * @return false|mixed
     */
    public function gaParseCookie()
    {
        if (!$this->ga_session) {
            return false;
        }

        $ver = false;
        $domain = false;
        $cid1 = false;
        $cid2 = false;

        [$ver, $domain, $cid1, $cid2] = explode('.', $this->ga_session, 4);
        $contents = ['version' => $ver, 'domainDepth' => $domain, 'cid' => $cid1.'.'.$cid2];

        return $contents['cid'];
    }

    /**
     * Data send with curl
     *
     * @param array $data
     *
     * @return bool|string
     */
    public function gaSendData(array $data)
    {
        $post_url = 'https://www.google-analytics.com/collect?';
        $post_url .= http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Data send with curl
     *
     * @param array $data
     *
     * @return bool|string
     */
    public function ga4SendData($data)
    {
        $post_url = 'https://www.google-analytics.com/mp/collect?measurement_id='
            .$this->tracked_id.'&api_secret='.$this->api_secret;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * Tracking Universal Ga
     *
     * @param $category
     * @param $action
     * @param $label
     * @param array $products
     *
     * @return bool
     */
    public function gaSendEvent($category = null, $action = null, $label = null, array $products = [])
    :bool
    {
        $data = [
            'v' => 1,
            'tid' => $this->tracked_id,
            'cid' => $this->gaParseCookie(),
            't' => 'event',
            'ec' => $category, //(Required)
            'ea' => $action, //(Required)
            'el' => $label,
        ];

        $data_merge = array_merge($data, $products);
        $this->gaSendData($data_merge);

        return (true);
    }

    /**
     * Tracking GA 4
     * @param array $products
     *
     * @return bool
     */
    public function ga4SendEvent(array $products = [])
    :bool
    {
        $data = [
            'client_id' => $this->gaParseCookie()
        ];

        $data_merge = array_merge($data, $products);
        $this->ga4SendData((json_encode($data_merge, JSON_PRETTY_PRINT)));

        return (true);
    }
}
