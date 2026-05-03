<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Transfer = 'transfer';
    case Correction = 'correction';
}
