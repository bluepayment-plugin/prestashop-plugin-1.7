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

if (!defined('_PS_VERSION_')) {
    exit;
}

class AnalyticsTracking
{

    private $tracked_id;
    private $ga_session;

    public function __construct($tracked_id, $ga_session)
    {
        $this->tracked_id = $tracked_id;
        $this->ga_session = $ga_session;
    }

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

    public function gaSendPageView($hostname = null, $page = null, $title = null)
    {
        $data = [
            'v' => 1,
            'tid' => $this->tracked_id,
            'cid' => $this->gaParseCookie(),
            't' => 'pageview',
            'dh' => $hostname,
            'dp' => $page,
            'dt' => $title
        ];
        $this->gaSendData($data);
    }

    public function gaSendEvent($category = null, $action = null, $label = null, $products = [])
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
    }
}
