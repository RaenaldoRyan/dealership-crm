<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
        });

        foreach (DB::table('deals')->where('stage', 'sold')->whereNull('closed_at')->orderBy('id')->cursor() as $deal) {
            DB::table('deals')->where('id', $deal->id)->update([
                'closed_at' => $deal->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
