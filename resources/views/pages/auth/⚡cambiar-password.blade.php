<?php

use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::auth')] #[Title('Cambiar contraseña')] class extends Component {
    use PasswordValidationRules;

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Si el usuario no tiene un cambio pendiente, no tiene nada que hacer aquí.
     */
    public function mount(): void
    {
        if (! Auth::user()->debe_cambiar_contrasena) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /**
     * Guarda la nueva contraseña y libera el acceso al resto de la aplicación.
     */
    public function cambiar(): void
    {
        $user = Auth::user();

        try {
            $validated = $this->validate(
                ['password' => $this->passwordRules()],
                [],
                ['password' => 'nueva contraseña'],
            );

            if (Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'La nueva contraseña debe ser distinta a la actual.',
                ]);
            }
        } catch (ValidationException $e) {
            $this->reset('password', 'password_confirmation');

            throw $e;
        }

        $user->update([
            'password' => $validated['password'],
            'debe_cambiar_contrasena' => false,
        ]);

        session()->flash('status', 'Contraseña actualizada correctamente.');

        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header
        title="Cambiar contraseña"
        description="Por seguridad, debe definir una nueva contraseña antes de continuar."
    />

    <form method="POST" wire:submit="cambiar" class="flex flex-col gap-6">
        <flux:input
            wire:model="password"
            label="Nueva contraseña"
            type="password"
            required
            autofocus
            autocomplete="new-password"
            viewable
        />

        <flux:input
            wire:model="password_confirmation"
            label="Confirmar contraseña"
            type="password"
            required
            autocomplete="new-password"
            viewable
        />

        <flux:button type="submit" variant="primary" class="w-full" data-test="cambiar-password-button">
            Guardar y continuar
        </flux:button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="text-center">
        @csrf
        <flux:button type="submit" variant="ghost" size="sm" data-test="logout-button">
            Cerrar sesión
        </flux:button>
    </form>
</div>
