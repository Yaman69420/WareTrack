<?php

namespace App\Enums;

/**
 * De vier soorten stockbewegingen in het audit-trail.
 *
 * Backed enum: de databank kent geen PHP-enums en slaat de string-waarde op;
 * de cast op StockMovement::$type levert in PHP de type-veilige case. Zo kan
 * er nooit een onbekend type in de tabel belanden. Correction bestaat omdat
 * bewegingen onveranderlijk zijn (zie StockMovementPolicy): een fout wordt
 * rechtgezet met een nieuwe boeking, nooit door de oude aan te passen.
 */
enum StockMovementType: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
    case Transfer = 'transfer';
    case Correction = 'correction';
}
