<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';

    // Create/Edit modal
    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            // OR-zoekvoorwaarden gegroepeerd zodat een latere AND-filter niet
            // door de OR omzeild kan worden (AND/OR-precedentie).
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function roles(): array
    {
        return UserRole::cases();
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'editingId']);
        $this->role = UserRole::WarehouseWorker->value;
        $this->resetValidation();
        $this->showModal = true;
    }

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

    public function save(): void
    {
        $emailRule = $this->editingId
            ? "required|email|max:150|unique:users,email,{$this->editingId}"
            : 'required|email|max:150|unique:users,email';

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
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'role' => $this->role,
            ];
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            activity()->causedBy(auth()->user())->performedOn($user)->log('updated');
            Flux::toast(__('User updated.'), variant: 'success');
        } else {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'email_verified_at' => now(),
            ]);
            activity()->causedBy(auth()->user())->performedOn($user)->log('created');
            Flux::toast(__('User created.'), variant: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'email', 'password', 'role', 'editingId']);
        unset($this->users);
    }

    public function delete(User $user): void
    {
        if ($user->id === auth()->id()) {
            Flux::toast(__('You cannot delete your own account.'), variant: 'danger');

            return;
        }

        try {
            $user->delete();
            activity()->causedBy(auth()->user())->performedOn($user)->log('deleted');
            Flux::toast(__('User deleted.'), variant: 'success');
            unset($this->users);
        } catch (QueryException $e) {
            Flux::toast(__('Cannot delete user: they have linked stock movements or deliveries.'), variant: 'danger');
        }
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}
