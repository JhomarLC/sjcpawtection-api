<?php

use App\Models\MedicationName;
use App\Models\Pet;
use App\Models\Veterinarians;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MedicationName::class);
            $table->foreignIdFor(Pet::class);
            $table->foreignIdFor(Veterinarians::class);
            $table->string('batch_number');
            $table->string('fee')->default('0');
            $table->date('expiry_date');
            $table->date('next_vaccination');
            $table->timestamp('medication_date')->useCurrent();
            $table->enum('remarks', ['Walk-In', 'Mass']);
            $table->enum('or_number', ['Registered', 'Unregistered']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};