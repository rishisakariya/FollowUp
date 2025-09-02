<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('otp');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->foreign('email')->references('email')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
