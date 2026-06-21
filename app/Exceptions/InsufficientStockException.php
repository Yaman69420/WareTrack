<?php

namespace App\Exceptions;

use Exception;

/**
 * Domeinuitzondering voor een uitgifte of transfer die meer vraagt dan er beschikbaar is.
 *
 * Eigen exception-klasse zodat Livewire-componenten dit verwachte geval gericht kunnen
 * opvangen en een nette foutmelding tonen, terwijl onverwachte fouten gewoon
 * doorbubbelen naar de algemene afhandeling. Het gooien ervan rolt bovendien de
 * lopende DB-transactie in StockService automatisch terug.
 */
class InsufficientStockException extends Exception
{
    /**
     * Bouwt de foutmelding op uit het gevraagde en het beschikbare aantal, zodat
     * meteen zichtbaar is hoe groot het tekort was op het moment van de aanvraag.
     */
    public function __construct(int $requested, int $available)
    {
        parent::__construct(
            "Insufficient stock: requested {$requested}, available {$available}."
        );
    }
}
