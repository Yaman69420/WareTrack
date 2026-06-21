<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Fortify-action die het wachtwoord van een gebruiker reset na een vergeten-flow.
 *
 * Afkomstig uit de officiële Livewire starter kit (laravel/livewire-starter-kit);
 * forceFill is hier veilig: het nieuwe wachtwoord is net gevalideerd en de
 * gebruiker bewees eigenaarschap via de getekende resetlink uit de mail.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
