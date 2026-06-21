<?php

namespace App\Enums;

/**
 * De twee gebruikersrollen van WareTrack.
 *
 * Backed enum met string-waarden: de databank kent geen PHP-enums en bewaart
 * dus de ruwe string ('admin'). Via de enum-cast op User::$role komt die terug
 * als type-veilige case, zodat een tikfout zoals 'amdin' een harde fout geeft
 * in plaats van een stille autorisatiebug die alles weigert (of toelaat).
 */
enum UserRole: string
{
    case Admin = 'admin';
    case WarehouseWorker = 'warehouse_worker';
}
