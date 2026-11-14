<?php

use App\Inventory\Support\Registration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Plates are normalised in PHP so the unique index matches on SQLite, MySQL, and Postgres.
     * Rolling back drops the added columns and the unique index.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('colour')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('transmission')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('vin', 17)->nullable();
            $table->string('status')->default('in_stock');
        });

        foreach (DB::table('vehicles')->select('id', 'registration')->orderBy('id')->cursor() as $vehicle) {
            $normalized = Registration::normalize((string) $vehicle->registration);

            if ($normalized === $vehicle->registration) {
                continue;
            }

            DB::table('vehicles')->where('id', $vehicle->id)->update([
                'registration' => $normalized,
            ]);
        }

        Schema::table('vehicles', function (Blueprint $table) {
            $table->unique('registration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique(['registration']);
            $table->dropColumn(['colour', 'fuel_type', 'transmission', 'mileage', 'vin', 'status']);
        });
    }
};
