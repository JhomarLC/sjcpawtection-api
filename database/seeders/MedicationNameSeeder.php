<?php

namespace Database\Seeders;

use App\Models\MedicationName;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicationNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dogVaccineList = [
            "Anti-Rabies",
            "Parvoviridae",
            "Distemper",
            "Canine influenza",
            "Bordetella",
            "Leptospirosis",
            "Lyme disease",
            "Kennel cough",
            "Parainfluenza",
            "Canine hepatitis",
            "Adenovirus infection",
            "Hepatitis",
            "Lyme vaccine",
            "Combination vaccines",
            "Leptospirosis vaccine",
            "Coronavirus disease 2019",
            "Rattlesnake vaccine",
            "Canine adenovirus 2",
            "Dhpp, leptospirosis, rabies",
            "Giardia",
        ];

        $dogDewormingList = [
            "Roundworm",
            "Hookworm",
            "Whipworm",
            "Tapeworm",
            "Heartworm",
        ];

        foreach ($dogVaccineList as $name) {
            MedicationName::factory()->create([
                'medication_type_id' => 1,
                'name' => $name,
            ]);
        }

        foreach ($dogDewormingList as $name) {
            MedicationName::factory()->create([
                'medication_type_id' => 2,
                'name' => $name,
            ]);
        }
    }

}
