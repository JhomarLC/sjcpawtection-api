<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "or_number" => $this->or_number,
            "batch_number" => $this->batch_number,
            "fee" => $this->fee,
            "expiry_date" => $this->expiry_date,
            "next_vaccination" => $this->next_vaccination,
            "medication_date" => $this->medication_date,
            "remarks" => $this->remarks,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "pet" => new PetResource($this->whenLoaded('pet')),
            "veterinarian" => new VeterinariansResource($this->whenLoaded('veterinarian')),
            "medicationname" => new MedicationNameResource($this->whenLoaded('medicationname')),
        ];
    }
}