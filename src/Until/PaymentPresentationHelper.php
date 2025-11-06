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

namespace BluePayment\Until;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Central place to prepare and assign payment presentation (texts) to Smarty
 */
class PaymentPresentationHelper
{
    public static function assign(array $data, string $defaultName, int $gatewayId): void
    {
        $short = !empty($data['short_description'])
            ? $data['short_description']
            : (!empty($data['group_short_description']) ? $data['group_short_description'] : '');

        $desc = !empty($data['description'])
            ? $data['description']
            : (!empty($data['group_description']) ? $data['group_description'] : '');

        $descUrl = !empty($data['description_url']) ? $data['description_url'] : '';

        \Context::getContext()->smarty->assign([
            'bm_short_description' => $short,
            'bm_description' => $desc,
            'bm_description_url' => $descUrl,
            'bm_gateway_name' => ($data['gateway_name'] ?? $defaultName),
            'bm_gateway_id' => $gatewayId,
        ]);
    }
}
