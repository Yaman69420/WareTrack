<?php

namespace App\Enums;

/**
 * Levenscyclus van een levering: aangekondigd, deels of volledig ontvangen.
 *
 * Backed enum: de databank kent geen PHP-enums en bewaart de string-waarde;
 * de cast op Delivery::$status maakt er in PHP weer een type-veilige case
 * van. Partial dekt de praktijk van deelzendingen: een deel van de lijnen
 * is binnen, de rest blijft verwacht en de levering blijft open staan.
 */
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Partial = 'partial';
}
