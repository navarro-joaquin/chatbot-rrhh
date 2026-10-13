<?php

namespace App\Livewire\Forms;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    #[Validate]
    public ?string $name = '';

    #[Validate]
    public ?string $email = '';

    #[Validate]
    public ?string $rol = Rol::RecursosHumanos->value;

    #[Validate]
    public ?string $password = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.($this->user?->id ?? 'NULL')],
            'rol' => ['required', Rule::enum(Rol::class)],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'rol' => 'perfil',
            'password' => 'contraseña',
        ];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->rol = $user->rol?->value ?? Rol::RecursosHumanos->value;
        $this->password = ''; // No cargar la contraseña por seguridad
    }

    public function save(): bool
    {
        $this->validate();

        if ($this->user?->is(auth()->user()) && $this->user->rol?->value !== $this->rol) {
            $this->addError('rol', 'No puedes cambiar tu propio perfil.');

            return false;
        }

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol,
        ];

        if (! empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->user) {
            $this->user->update($data);
        } else {
            User::create($data);
        }

        $this->reset();

        return true;
    }
}
