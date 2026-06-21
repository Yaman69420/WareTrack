<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Livewire\Concerns\WithSorting;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Beheerscherm voor gebruikersaccounts: zoeken plus CRUD via een modal, met
 * rollen uit de UserRole-enum (admin of magazijnier).
 *
 * Bevat twee beschermingen bij verwijderen: een gebruiker kan zichzelf nooit
 * wissen, en accounts met gekoppelde stockbewegingen of leveringen blijven
 * bestaan zodat de historiek altijd haar auteur behoudt.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;
    use WithSorting;

    /** Kolommen waarop gesorteerd mag worden (whitelist voor orderBy). */
    protected array $sortable = ['name', 'email', 'role'];

    public string $search = '';

    // Create/Edit modal
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    /**
     * Terug naar pagina 1 bij een nieuwe zoekterm, anders kan de gebruiker op
     * een lege pagina van het gefilterde resultaat belanden.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Doorzoekbare, gepagineerde gebruikerslijst, alfabetisch op naam.
     */
    #[Computed]
    public function users()
    {
        return User::query()
            // OR-zoekvoorwaarden gegroepeerd zodat een latere AND-filter niet
            // door de OR omzeild kan worden (AND/OR-precedentie).
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            // Klikbare kolomkoppen; zonder keuze blijft alfabetisch op naam de default.
            ->tap(fn ($q) => $this->applySort($q, 'name', 'asc'))
            ->paginate(15);
    }

    /**
     * Alle rollen uit de UserRole-enum, voor de rol-select in het formulier.
     */
    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
    }

    /**
     * Opent de modal in create-modus met een leeg formulier. Magazijnier is
     * de standaardrol (minste rechten); admin moet een bewuste keuze zijn.
     */
    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'editingId']);
        $this->role = UserRole::WarehouseWorker->value;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Opent de modal in edit-modus. Het wachtwoordveld blijft bewust leeg:
     * enkel een ingevulde waarde leidt bij save tot een wachtwoordwijziging.
     */
    public function openEdit(User $user): void
    {
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role->value;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * Eén save-methode voor create én update; $editingId bepaalt de modus.
     * Wachtwoorden worden altijd gehasht opgeslagen, nooit in platte tekst.
     */
    public function save(): void
    {
        // Bij bewerken moet de unique-check het eigen record negeren, anders
        // zou elke update falen op het bestaande (eigen) e-mailadres.
        $emailRule = $this->editingId
            ? "required|email|max:150|unique:users,email,{$this->editingId}"
            : 'required|email|max:150|unique:users,email';

        // Bij bewerken is het wachtwoord optioneel: leeg laten betekent
        // "behoud het huidige wachtwoord".
        $passwordRule = $this->editingId
            ? 'nullable|string|min:8'
            : 'required|string|min:8';

        $this->validate([
            'name' => 'required|string|max:150',
            'email' => $emailRule,
            'password' => $passwordRule,
            'role' => 'required|in:admin,warehouse_worker',
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ];
            // Enkel hashen en meesturen als er effectief een nieuw wachtwoord is.
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            activity()->causedBy(auth()->user())->performedOn($user)->log('updated');
            Flux::toast(__('User updated.'), variant: 'success');
        } else {
            $this->authorize('create', User::class);

            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                // Door een admin aangemaakt account: e-mailverificatie is overbodig.
                'email_verified_at' => now(),
            ]);
            activity()->causedBy(auth()->user())->performedOn($user)->log('created');
            Flux::toast(__('User created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'role', 'editingId']);
        unset($this->users);
    }

    /**
     * Verwijdert een gebruiker, met twee guards: nooit het eigen account, en
     * nooit een account waarnaar nog bewegingen of leveringen verwijzen.
     */
    public function delete(User $user): void
    {
        $this->authorize('delete', $user);

        // Zelf-verwijdering blokkeren: anders sluit een admin zichzelf buiten
        // en kan het systeem zelfs zonder enige beheerder vallen.
        if ($user->id === auth()->id()) {
            Flux::toast(__('You cannot delete your own account.'), variant: 'danger');

            return;
        }

        // De databank weigert de delete via foreign keys zodra er bewegingen of
        // leveringen aan de gebruiker hangen; die QueryException vertalen we
        // naar een verstaanbare melding in plaats van een serverfout.
        try {
            $user->delete();
            activity()->causedBy(auth()->user())->performedOn($user)->log('deleted');
            Flux::toast(__('User deleted.'), variant: 'success');
            unset($this->users);
        } catch (QueryException $e) {
            Flux::toast(__('Cannot delete user: they have linked stock movements or deliveries.'), variant: 'danger');
        }
    }

    /**
     * Rendert de gebruikerslijst-view.
     */
    public function render()
    {
        return view('livewire.users.index');
    }
}
