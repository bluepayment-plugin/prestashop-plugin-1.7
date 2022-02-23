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

    function __construct($tracked_id)
    {
        $this->tracked_id = $tracked_id;
    }

    function gaParseCookie() {
        if (isset($_COOKIE['_ga'])) {
            list($version, $domainDepth, $cid1, $cid2) = explode('.', $_COOKIE["_ga"], 4);
            $contents = array('version' => $version, 'domainDepth' => $domainDepth, 'cid' => $cid1 . '.' . $cid2);
            $cid = $contents['cid'];
        } else {
            $cid = $this->gaGenerateUUID();
        }
        return $cid;
    }

    function gaGenerateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }


    function gaSendData(array $data) {
        $post_url = 'https://www.google-analytics.com/collect?';
        $post_url .= http_build_query( $data );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $post_url);
        curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }


    function ga_send_pageview($hostname=null, $page=null, $title=null) {
        $data = array(
            'v' => 1,
            'tid' => $this->tracked_id,
            'cid' => $this->gaParseCookie(),
            't' => 'pageview',
            'dh' => $hostname,
            'dp' => $page,
            'dt' => $title
        );
        $this->gaSendData($data);
    }

    function ga_send_event($category=null, $action=null, $label=null) {
        $data = array(
            'v' => 1,
            'tid' => $this->tracked_id,
            'cid' => $this->gaParseCookie(),
            't' => 'event',
            'ec' => $category, //(Required)
            'ea' => $action, //(Required)
            'el' => $label
        );
        $this->gaSendData($data);
    }

}