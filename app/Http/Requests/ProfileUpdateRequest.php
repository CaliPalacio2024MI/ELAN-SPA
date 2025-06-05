<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Autoriza la solicitud (siempre true para usuarios autenticados).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar perfil.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                // Validar unicidad ignorando el usuario actual
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
