<?php

namespace App\Enums;

enum MovementActionType: string
{
    case Delivery = 'delivery';
    case Collection = 'collection';
    case ExchangeDelivery = 'exchange_delivery';
    case ExchangeCollection = 'exchange_collection';
    case TradeIn = 'trade_in';
    case YardReceipt = 'yard_receipt';
}
