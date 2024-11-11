<?php

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
        Schema::create('pet_owners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('image');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('addr_zone', 50);
            $table->enum('addr_brgy',
                [
                    "A. Pascual",
                    "Abar Ist",
                    "Abar 2nd",
                    "Bagong Sikat",
                    "Caanawan",
                    "Calaocan",
                    "Camanacsacan",
                    "Culaylay",
                    "Dizol",
                    "Kaliwanagan",
                    "Kita-Kita",
                    "Malasin",
                    "Manicla",
                    "Palestina",
                    "Parang Mangga",
                    "Villa Joson",
                    "Pinili",
                    "Rafael Rueda, Sr. Pob.",
                    "Ferdinand E. Marcos Pob.",
                    "Canuto Ramos Pob.",
                    "Raymundo Eugenio Pob.",
                    "Crisanto Sanchez Pob.",
                    "Porais",
                    "San Agustin",
                    "San Juan",
                    "San Mauricio",
                    "Santo Niño 1st",
                    "Santo Niño 2nd",
                    "Santo Tomas",
                    "Sibut",
                    "Sinipit Bubon",
                    "Santo Niño 3rd",
                    "Tabulac",
                    "Tayabo",
                    "Tondod",
                    "Tulat",
                    "Villa Floresca",
                    "Villa Marina"
                ]
            );
            $table->string('phone_number', 11);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_owners');
    }
};
