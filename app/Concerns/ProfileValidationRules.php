<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Herbruikbare validatieregels voor profielgegevens (naam en e-mailadres).
 *
 * Gedeeld tussen registratie en profielbewerking; de optionele $userId zorgt
 * dat de unique-check op e-mail het eigen record overslaat bij een update,
 * terwijl een nieuwe registratie wél elke bestaande mailbox blokkeert.
 */
/**
 * Herbruikbare profielvalidatieregels (naam, e-mail) uit de starter kit,
 * gedeeld door registratie en de profiel-instellingenpagina.
 */
trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Get the validation rules used to validate user emails.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'email',
            'max:255',
            // Bij een update telt het eigen e-mailadres niet als duplicaat;
            // bij registratie ($userId null) blokkeert élk bestaand adres.
            $userId === null
                ? Rule::unique(User::class)
                : Rule::unique(User::class)->ignore($userId),
        ];
    }
}
