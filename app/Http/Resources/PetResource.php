<?php

namespace App\Http\Resources;

use App\Models\PetOwner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PetResource extends JsonResource
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
            'name' => $this->name,
            'image' => $this->image,
            'breed' => $this->breed,
            'gender' => $this->gender,
            'color_description' => $this->color_description,
            'weight' => $this->weight,
            'size' => $this->size,
            'date_of_birth' => $this->date_of_birth,
            'age' => $this->formatted_age,
            'status' => $this->status,
            'pet_type' => $this->pet_type,
            'petowner' => new PetOwnerResource($this->whenLoaded('petowner')),
        ];
    }
}