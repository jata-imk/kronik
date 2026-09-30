<?php

namespace App\Services\Credito;

use App\Support\Decimal;
use Illuminate\Validation\ValidationException;

/** Splits an allocation, not the whole payment, against posted concept/tax balances. */
final class RepartoProporcional
{
    public function repartir(string $abono, string $conceptoPendiente, string $impuestoPendiente): array
    {
        foreach ([$abono, $conceptoPendiente, $impuestoPendiente] as $importe) {
            if (! preg_match('/^\d{1,14}(\.\d{1,2})?$/D', $importe)) {
                throw ValidationException::withMessages(['importe' => 'Indica importes no negativos, con hasta dos decimales.']);
            }
        }
        $total = Decimal::add($conceptoPendiente, $impuestoPendiente, 2);
        if (Decimal::compare($abono, $total) > 0) {
            throw ValidationException::withMessages(['importe' => 'El abono excede el concepto y su impuesto pendientes. No se crea saldo a favor.']);
        }
        if (Decimal::compare($total, '0') === 0) {
            return ['concepto' => '0.00', 'impuesto' => '0.00'];
        }
        // Final allocation absorbs residual cents; never exceed either balance.
        $concepto = Decimal::round(Decimal::div(Decimal::mul($abono, $conceptoPendiente), $total));
        $impuesto = Decimal::sub($abono, $concepto, 2);

        return ['concepto' => $concepto, 'impuesto' => $impuesto];
    }
}
