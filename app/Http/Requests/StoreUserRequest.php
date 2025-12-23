<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'document' => [
                'required',
                'string',
                'unique:users,document',
                'regex:/^[0-9]{11}$|^[0-9]{14}$/', // CPF (11) ou CNPJ (14)
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'max:255',
            ],
            'type' => [
                'required',
                Rule::in([User::TYPE_COMMON, User::TYPE_MERCHANT]),
            ],
            'balance' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres',
            'name.max' => 'O nome deve ter no máximo 255 caracteres',
            
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser válido',
            'email.unique' => 'Este e-mail já está em uso',
            
            'document.required' => 'O documento (CPF/CNPJ) é obrigatório',
            'document.unique' => 'Este documento já está cadastrado',
            'document.regex' => 'O documento deve ter 11 dígitos (CPF) ou 14 dígitos (CNPJ)',
            
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            
            'type.required' => 'O tipo de usuário é obrigatório',
            'type.in' => 'O tipo deve ser "common" ou "merchant"',
            
            'balance.numeric' => 'O saldo deve ser numérico',
            'balance.min' => 'O saldo não pode ser negativo',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'document' => 'documento',
            'password' => 'senha',
            'type' => 'tipo',
            'balance' => 'saldo',
        ];
    }
}
