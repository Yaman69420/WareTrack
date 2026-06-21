<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

/**
 * Herbruikbare wachtwoord-validatieregels voor alle auth-flows.
 *
 * Eén trait voor registratie, reset én profielwijziging, zodat het beleid
 * nergens uit elkaar kan lopen. Password::default() pakt automatisch de
 * strikte productieregels uit AppServiceProvider op.
 */
/**
 * Herbruikbare wachtwoordregels (trait), gedeeld door registratie, reset en
 * gebruikersbeheer — afkomstig uit de Livewire starter kit.
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::default(), 'confirmed'];
    }

    /**
     * Get the validation rules used to validate the current password.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function currentPasswordRules(): array
    {
        return ['required', 'string', 'current_password'];
    }
}
