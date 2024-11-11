<?php

use App\Models\PetOwner;
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
        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(PetOwner::class);
            $table->string('image');
            $table->string('name', 100);
            $table->enum('gender', ['Male', 'Female']);
            $table->string('breed', 100);
            $table->string('color_description', 100);
            $table->string('size', 100);
            $table->float('weight');
            $table->date('date_of_birth');
            $table->enum('status', ['pending', 'approved', 'deceased', 'declined'])->default('pending');
            $table->enum('pet_type', ['dog', 'cat'])->default('dog');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};