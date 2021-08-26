<?php

abstract class OrderStatusMessageDictionary
{
    const PENDING = 1;

    const ORDER_STATUS_MESSAGE = [
        self::PENDING => 'Payment in progress',
    ];

    public static function getMessage($order_status_id)
    {
        return self::hasKey($order_status_id) ? self::ORDER_STATUS_MESSAGE[$order_status_id] : null;
    }

    private static function hasKey($order_status_id): bool
    {
        return array_key_exists($order_status_id, self::ORDER_STATUS_MESSAGE);
    }
}
