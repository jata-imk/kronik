<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActivityLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The query service authorizes the validated selected team for both
        // listing and export, rather than assuming the current team.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'sucursal_id' => ['nullable', 'integer', 'exists:sucursales,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'string', 'max:100'],
            'subject_type' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ];
    }

    public function attributes(): array
    {
        return ['search' => 'búsqueda', 'team_id' => 'equipo seleccionado', 'sucursal_id' => 'sucursal seleccionada',
            'user_id' => 'usuario', 'event' => 'evento', 'subject_type' => 'sujeto',
            'date_from' => 'fecha inicial', 'date_to' => 'fecha final', 'per_page' => 'registros por página'];
    }
}
