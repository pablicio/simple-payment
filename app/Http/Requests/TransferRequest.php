<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TransferRequest extends FormRequest
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
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
                'decimal:0,2', // Máximo 2 casas decimais
            ],
            'payer' => [
                'required',
                'integer',
                'exists:users,id',
                'different:payee',
            ],
            'payee' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Validação adicional: detectar possível fraude
            $value = $this->input('value');
            $payer = $this->input('payer');
            
            // Alertar sobre valores muito altos
            if ($value > 10000) {
                \Log::alert('High value transfer validation', [
                    'value' => $value,
                    'payer' => $payer,
                    'ip' => $this->ip(),
                ]);
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar valor: remover caracteres não numéricos exceto ponto e vírgula
        if ($this->has('value')) {
            $value = $this->input('value');
            
            if (is_string($value)) {
                // Remove espaços e caracteres especiais
                $value = preg_replace('/[^\d.,]/', '', $value);
                
                // Substitui vírgula por ponto
                $value = str_replace(',', '.', $value);
                
                $this->merge(['value' => $value]);
            }
        }
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'value.required' => 'O valor da transferência é obrigatório',
            'value.numeric' => 'O valor deve ser numérico',
            'value.min' => 'O valor mínimo é R$ 0,01',
            'value.max' => 'O valor máximo é R$ 999.999,99',
            'value.decimal' => 'O valor deve ter no máximo 2 casas decimais',
            
            'payer.required' => 'O pagador é obrigatório',
            'payer.integer' => 'O ID do pagador deve ser um número inteiro',
            'payer.exists' => 'O pagador não existe',
            'payer.different' => 'O pagador não pode ser o mesmo que o recebedor',
            
            'payee.required' => 'O recebedor é obrigatório',
            'payee.integer' => 'O ID do recebedor deve ser um número inteiro',
            'payee.exists' => 'O recebedor não existe',
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
            'value' => 'valor',
            'payer' => 'pagador',
            'payee' => 'recebedor',
        ];
    }
}
