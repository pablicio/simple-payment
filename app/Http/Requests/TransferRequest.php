<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            ],
            'payer' => [
                'required',
                'integer',
                'exists:users,id',
                'different:payee', // Não pode ser o mesmo que payee
            ],
            'payee' => [
                'required',
                'integer',
                'exists:users,id',
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
            'value.required' => 'O valor da transferência é obrigatório',
            'value.numeric' => 'O valor deve ser numérico',
            'value.min' => 'O valor mínimo é R$ 0,01',
            'value.max' => 'O valor máximo é R$ 999.999,99',
            
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
