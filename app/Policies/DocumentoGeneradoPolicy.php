<?php

namespace App\Policies;

use App\Models\DocumentoGenerado;
use App\Models\SolicitudPaquete;
use App\Models\User;

class DocumentoGeneradoPolicy
{
    public function view(User $user, DocumentoGenerado $documento): bool
    {
        return $user->can('read documentos') && $user->can('view', $documento->cliente) && $this->canReadPackage($user, $documento);
    }

    public function download(User $user, DocumentoGenerado $documento): bool
    {
        return $user->can('download documentos') && $user->can('view', $documento->cliente) && $this->canReadPackage($user, $documento);
    }

    private function canReadPackage(User $user, DocumentoGenerado $documento): bool
    {
        return $documento->documentable_type !== (new SolicitudPaquete)->getMorphClass()
            || ($documento->documentable && $user->can('viewPackage', $documento->documentable->solicitud));
    }
}
