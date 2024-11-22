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
        return [
            "id" => $this->id,
            "name" => $this->name,
            "date_time" => $this->date_time,
            "place" => $this->place,
            "status" => $this->getStatus(),
            "description" => $this->description,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
    /**
     * Determine the status of the event based on the current date and time.
     *
     * @return string
     */
    protected function getStatus(): string
    {
        $currentDateTime = now(); // Current date and time
        if ($this->date_time > $currentDateTime) {
            return "Upcoming";
        } else {
            return "Done";
        }
    }
}

// return [
//     'id' => $this->id,
//     'name' => $this->name,
//     'date_time' => Carbon::parse($this->date_time)->format('F d, Y | h:m A'), // Format to "January 01, 2024"
//     'description' => $this->description,
//     // Include other necessary fields
// ];