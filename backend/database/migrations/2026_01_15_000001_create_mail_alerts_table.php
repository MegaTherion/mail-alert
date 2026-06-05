<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('rule');
            $table->enum('priority', ['high', 'emergency']);
            $table->string('from_address');
            $table->string('subject');
            $table->text('snippet');
            $table->dateTime('timestamp');
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_alerts');
    }
};
