<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
                'min:3',
                'max:255',
                'regex:/^[\pL\s\-\']+$/u', // Unicode letters, espaços, hífens e apóstrofos
            ],
            'email' => [
                'required',
                'email:rfc,dns', // Validação completa com verificação de DNS
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Formato adicional
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
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // Verifica se a senha vazou em data breaches
                'confirmed', // Requer password_confirmation
            ],
            'password_confirmation' => [
                'required',
                'string',
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
                'decimal:0,2',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar dados antes da validação
        $data = [];

        // Normalizar nome: remover espaços extras
        if ($this->has('name')) {
            $data['name'] = preg_replace('/\s+/', ' ', trim($this->input('name')));
        }

        // Normalizar email: lowercase e trim
        if ($this->has('email')) {
            $data['email'] = strtolower(trim($this->input('email')));
        }

        // Normalizar document: remover caracteres não numéricos
        if ($this->has('document')) {
            $data['document'] = preg_replace('/\D/', '', $this->input('document'));
        }

        // Normalizar balance
        if ($this->has('balance')) {
            $balance = $this->input('balance');
            if (is_string($balance)) {
                $balance = str_replace([',', ' '], ['.', ''], $balance);
                $data['balance'] = $balance;
            }
        }

        $this->merge($data);
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
            'name.regex' => 'O nome deve conter apenas letras e espaços',
            
            'email.required' => 'O e-mail é obrigatório',
            'email.email' => 'O e-mail deve ser válido',
            'email.unique' => 'Este e-mail já está em uso',
            'email.regex' => 'O formato do e-mail é inválido',
            
            'document.required' => 'O documento (CPF/CNPJ) é obrigatório',
            'document.unique' => 'Este documento já está cadastrado',
            'document.regex' => 'O documento deve ter 11 dígitos (CPF) ou 14 dígitos (CNPJ)',
            
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres',
            'password.confirmed' => 'As senhas não conferem',
            'password.letters' => 'A senha deve conter letras',
            'password.mixed_case' => 'A senha deve conter letras maiúsculas e minúsculas',
            'password.numbers' => 'A senha deve conter números',
            'password.symbols' => 'A senha deve conter caracteres especiais',
            'password.uncompromised' => 'Esta senha foi comprometida em vazamentos de dados. Por favor, escolha outra',
            
            'password_confirmation.required' => 'A confirmação de senha é obrigatória',
            
            'type.required' => 'O tipo de usuário é obrigatório',
            'type.in' => 'O tipo deve ser "common" ou "merchant"',
            
            'balance.numeric' => 'O saldo deve ser numérico',
            'balance.min' => 'O saldo não pode ser negativo',
            'balance.decimal' => 'O saldo deve ter no máximo 2 casas decimais',
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
            'password_confirmation' => 'confirmação de senha',
            'type' => 'tipo',
            'balance' => 'saldo',
        ];
    }
}
