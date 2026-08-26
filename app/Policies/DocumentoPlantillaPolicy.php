<?php

namespace App\Policies;

use App\Models\DocumentoPlantilla;
use App\Models\DocumentoPlantillaVersion;
use App\Models\User;

class DocumentoPlantillaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('read plantillas-documentos');
    }

    public function view(User $user, DocumentoPlantilla $plantilla): bool
    {
        return $user->can('read plantillas-documentos');
    }

    public function create(User $user): bool
    {
        return $user->can('create plantillas-documentos');
    }

    public function update(User $user, DocumentoPlantilla $plantilla): bool
    {
        return $user->can('update plantillas-documentos');
    }

    public function version(User $user, DocumentoPlantilla $plantilla): bool
    {
        return $user->can('version plantillas-documentos');
    }

    public function activate(User $user, DocumentoPlantillaVersion $version): bool
    {
        return $user->can('activate plantillas-documentos');
    }

    public function retire(User $user, DocumentoPlantillaVersion $version): bool
    {
        return $user->can('retire plantillas-documentos');
    }

    public function generate(User $user, DocumentoPlantillaVersion $version): bool
    {
        return $user->can('generate documentos');
    }
}
