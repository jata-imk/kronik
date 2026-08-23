<?php

namespace App\Services\Credito;

use App\Models\ProductoVersion;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class ResolverComisionesCreditoSimple
{
    /**
     * @param  array<int, int>  $seleccionadas
     * @return array{obligatorias: Collection, aplicadas: Collection, opcionales_seleccionadas: Collection, excluidas: array<int, array<string, mixed>>}
     */
    public function resolver(ProductoVersion $version, array $seleccionadas): array
    {
        $version->loadMissing('comisiones.concepto');
        $ids = collect($seleccionadas)->map(fn ($id) => (int) $id)->unique()->values();
        $comisiones = $version->comisiones;
        $deterministica = fn ($item) => $item->esInicial() || $item->momento_cobro === 'cada_pago';
        $obligatorias = $comisiones->where('obligatoria', true)->filter($deterministica)->values();
        $opcionalesElegibles = $comisiones->where('obligatoria', false)->filter($deterministica)->values();

        $desconocidas = $ids->diff($opcionalesElegibles->pluck('id'));
        if ($desconocidas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'comisiones_opcionales' => 'Seleccione únicamente comisiones opcionales de inicio o de cada pago pertenecientes a esta versión.',
            ]);
        }

        $opcionalesSeleccionadas = $opcionalesElegibles->whereIn('id', $ids)->values();
        $aplicadas = $obligatorias->concat($opcionalesSeleccionadas)->values();
        $excluidas = $comisiones->reject(fn ($item) => $aplicadas->contains('id', $item->id))->map(function ($item) use ($deterministica) {
            $motivo = ! $deterministica($item)
                ? 'Requiere simular el evento o la liquidación en que se genera.'
                : 'Comisión opcional no seleccionada para este escenario.';

            return [
                'id' => $item->id,
                'concepto' => $item->concepto?->nombre,
                'momento' => $item->momento_cobro,
                'motivo' => $motivo,
            ];
        })->values()->all();

        return compact('obligatorias', 'aplicadas', 'opcionalesSeleccionadas', 'excluidas');
    }
}
