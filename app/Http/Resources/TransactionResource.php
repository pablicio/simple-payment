<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payer' => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
                'email' => $this->payer->email,
            ],
            'payee' => [
                'id' => $this->payee->id,
                'name' => $this->payee->name,
                'email' => $this->payee->email,
            ],
            'value' => $this->value,
            'status' => $this->status,
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
