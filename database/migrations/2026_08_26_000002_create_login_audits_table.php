<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rest_auditoria_login', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('rest_users')->nullOnDelete();
            $table->string('email');
            $table->ipAddress('ip_address');
            $table->boolean('success')->default(false);
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['email', 'ip_address', 'success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_auditoria_login');
    }
};
