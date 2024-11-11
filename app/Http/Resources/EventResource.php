<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}

// return [
//     'id' => $this->id,
//     'name' => $this->name,
//     'date_time' => Carbon::parse($this->date_time)->format('F d, Y | h:m A'), // Format to "January 01, 2024"
//     'description' => $this->description,
//     // Include other necessary fields
// ];