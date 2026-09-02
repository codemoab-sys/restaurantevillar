<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rest_orders', function (Blueprint $table) {
            $table->timestamp('issued_at')->nullable()->after('created_at');
        });

        DB::table('rest_orders')->update(['issued_at' => DB::raw('COALESCE(sent_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('rest_orders', function (Blueprint $table) {
            $table->dropColumn('issued_at');
        });
    }
};