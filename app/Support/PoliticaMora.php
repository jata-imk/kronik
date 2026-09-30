<?php

namespace App\Support;

use Illuminate\Support\Facades\Validator;

final class PoliticaMora
{
    public static function rules(string $prefix = 'version.'): array
    {
        return [
            $prefix.'politica_mora' => ['sometimes', 'nullable', 'array:gracia,intereses', 'size:2'],
            $prefix.'politica_mora.gracia' => ['required_with:'.$prefix.'politica_mora', 'in:efectiva,retroactiva'],
            $prefix.'politica_mora.intereses' => ['required_with:'.$prefix.'politica_mora', 'in:ambos,sustituye'],
        ];
    }

    public static function messages(): array
    {
        return [
            'version.politica_mora.array' => 'Revisa la configuración de mora; solo se admiten gracia y modalidad de intereses.',
            'version.politica_mora.size' => 'Define tanto la gracia como la modalidad de intereses, o conserva la política sin definir.',
            'version.politica_mora.gracia.required_with' => 'Selecciona cómo se aplican los días de gracia.',
            'version.politica_mora.gracia.in' => 'Selecciona gracia efectiva A o retroactiva B.',
            'version.politica_mora.intereses.required_with' => 'Selecciona ambos intereses o moratorio que sustituye al ordinario.',
            'version.politica_mora.intereses.in' => 'La modalidad de intereses debe ser ambos o moratorio que sustituye al ordinario.',
        ];
    }

    public static function validate(array $data): void
    {
        Validator::make(['version' => $data], self::rules(), self::messages())->validate();
    }
}
