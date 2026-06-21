<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Herbruikbaar sorteer-gedrag voor lijstcomponenten.
 *
 * De component definieert een whitelist `protected array $sortable` met de
 * kolomnamen die gesorteerd mogen worden — sorteren gebeurt met orderBy op
 * een kolomnaam, en die mag nooit rechtstreeks uit gebruikersinvoer komen
 * (kolomnamen zijn geen prepared-statement-parameters). De sorteerstaat
 * spiegelt naar de querystring zodat een gesorteerde weergave deelbaar is.
 */
trait WithSorting
{
    #[Url]
    public string $sortBy = '';

    #[Url]
    public string $sortDirection = 'asc';

    /**
     * Klik op een kolomkop: eerste klik sorteert oplopend, tweede klik
     * draait de richting om. Onbekende kolommen worden genegeerd (whitelist).
     */
    public function sort(string $column): void
    {
        if (! in_array($column, $this->sortable, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        // Terug naar pagina 1: de sortering verandert wat er op elke pagina staat.
        $this->resetPage();
    }

    /**
     * Past de gekozen sortering toe, of de meegegeven default zolang de
     * gebruiker nog niet op een kolomkop geklikt heeft.
     */
    protected function applySort($query, string $defaultColumn = 'created_at', string $defaultDirection = 'desc')
    {
        if ($this->sortBy !== '' && in_array($this->sortBy, $this->sortable, true)) {
            return $query->orderBy($this->sortBy, $this->sortDirection === 'desc' ? 'desc' : 'asc');
        }

        return $query->orderBy($defaultColumn, $defaultDirection);
    }
}
