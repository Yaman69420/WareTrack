<?php

namespace App\Enums;

enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Partial = 'partial';
}
