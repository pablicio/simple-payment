<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->id,
            'payer' => [
                'id' => $this->payer->id,
                'name' => $this->payer->name,
                'balance' => $this->payer->balance,
            ],
            'payee' => [
                'id' => $this->payee->id,
                'name' => $this->payee->name,
                'balance' => $this->payee->balance,
            ],
            'value' => $this->value,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'message' => 'Transfer completed successfully',
        ];
    }
}
