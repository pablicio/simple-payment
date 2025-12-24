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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Recebedor
            $table->string('type')->default('transfer_received'); // Tipo de notificação
            $table->string('channel')->default('api'); // api, email, sms
            $table->text('message'); // Mensagem enviada
            $table->json('payload')->nullable(); // Payload enviado à API
            $table->string('status')->default('pending'); // pending, sent, failed
            $table->text('response')->nullable(); // Resposta da API
            $table->integer('attempts')->default(0); // Número de tentativas
            $table->timestamp('sent_at')->nullable(); // Quando foi enviada
            $table->timestamp('failed_at')->nullable(); // Quando falhou definitivamente
            $table->timestamps();
            
            // Índices para consultas
            $table->index('transaction_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
