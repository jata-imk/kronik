<?php

namespace App\Policies;

use App\Models\ProductoVersion;
use App\Models\User;

class ProductoVersionPolicy
{
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
