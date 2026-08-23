<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rest_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('reference_date');                         // Fecha de las boletas resumidas
            $table->date('generation_date');                        // Fecha en que se envÃ­a el resumen
            $table->unsignedInteger('correlativo');                 // RC-yyyymmdd-N
            $table->string('identifier', 30);                       // RC-yyyymmdd-N
            $table->unsignedInteger('total_documents')->default(0); // Cantidad de boletas
            $table->decimal('total_amount', 14, 2)->default(0);     // Suma de totales

            // SUNAT (el RC se envÃ­a asÃ­ncrono: devuelve ticket y luego se consulta)
            $table->string('sunat_status', 30)->default('PENDING'); // PENDING | TICKET | ACCEPTED | OBSERVED | REJECTED | ERROR
            $table->string('ticket', 100)->nullable();
            $table->string('sunat_code', 10)->nullable();
            $table->text('sunat_description')->nullable();
            $table->string('xml_path')->nullable();
            $table->string('cdr_path')->nullable();
            $table->string('hash', 100)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('consulted_at')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('rest_users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reference_date', 'correlativo'], 'daily_summaries_date_corr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rest_daily_summaries');
    }
};
