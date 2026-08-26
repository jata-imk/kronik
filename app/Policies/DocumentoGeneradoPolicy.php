<?php

namespace App\Policies;

use App\Models\DocumentoGenerado;
use App\Models\User;

class DocumentoGeneradoPolicy
{
    public function view(User $user, DocumentoGenerado $documento): bool
    {
        return $user->can('read documentos') && $user->can('view', $documento->cliente);
    }

    public function download(User $user, DocumentoGenerado $documento): bool
    {
        return $user->can('download documentos') && $user->can('view', $documento->cliente);
    }
}
