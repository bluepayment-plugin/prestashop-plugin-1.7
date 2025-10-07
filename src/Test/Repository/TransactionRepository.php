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

namespace BluePayment\Test\Repository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Repository for BLIK transaction data
 */
final class TransactionRepository
{
    /**
     * Get transaction data from database
     */
    public function getTransactionData(string $orderId, string $blikCode)
    {
        $query = new \DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = \'' . pSQL($orderId) . '\'')
            ->where('blik_code = \'' . pSQL($blikCode) . '\'')
            ->select('*');

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    /**
     * Get transaction by order ID
     */
    public function getTransactionByOrderId(int $orderId)
    {
        $query = new \DbQuery();
        $query->from('blue_transactions')
            ->where('order_id LIKE \'' . (int) $orderId . '-%\'')
            ->orderBy('created_at DESC')
            ->select('*');

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    /**
     * Get transaction by exact order ID
     */
    public function getTransactionByOrderIdExact(string $orderId)
    {
        $query = new \DbQuery();
        $query->from('blue_transactions')
            ->where('order_id = \'' . pSQL($orderId) . '\'')
            ->select('*');

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query, false);
    }

    /**
     * Save or update transaction in database
     */
    public function saveTransaction($transaction, string $orderId, array $data): void
    {
        if (empty($transaction)) {
            \Db::getInstance()->insert('blue_transactions', $data);
        } else {
            unset($data['order_id']);
            \Db::getInstance()->update('blue_transactions', $data, 'order_id = \'' . pSQL($orderId) . '\'');
        }
    }
}
