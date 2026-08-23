<?php

namespace App\Policies;

use App\Models\ProductoCrediticio;
use App\Models\ProductoVersion;
use App\Models\User;

class ProductoCrediticioPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read productos-crediticios');
    }

    public function view(User $user, ProductoCrediticio $producto): bool
    {
        return $user->can('read productos-crediticios');
    }

    public function create(User $user): bool
    {
        return $user->can('create productos-crediticios');
    }

    public function update(User $user, ProductoCrediticio $producto): bool
    {
        return $user->can('update productos-crediticios');
    }

    public function version(User $user, ProductoCrediticio $producto): bool
    {
        return $user->can('version productos-crediticios');
    }

    public function activate(User $user, ProductoVersion $version): bool
    {
        return $user->can('activate productos-crediticios');
    }

    public function retire(User $user, ProductoVersion $version): bool
    {
        return $user->can('retire productos-crediticios');
    }

    public function simulate(User $user, ProductoVersion $version): bool
    {
        return $user->can('simulate productos-crediticios');
    }
}
