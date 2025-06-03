<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function getActionAttribute()
    {
        // Extrae la acción del nombre del permiso
        // Ej: "create users" -> "create"
        return explode(' ', $this->name)[0] ?? null;
    }

    public function getResourceAttribute()
    {
        // Extrae el recurso del nombre del permiso  
        // Ej: "create users" -> "users"
        $parts = explode(' ', $this->name);
        return count($parts) > 1 ? $parts[1] : null;
    }
}
