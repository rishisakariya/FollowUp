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
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('user_id'); // primary key, BIGINT UNSIGNED
            $table->string('name', 50); // required
            $table->string('email', 100)->unique(); // required + unique
            $table->string('password', 255); // required

            // ✅ Email verification timestamp
            $table->timestamp('email_verified_at')->nullable();

            // ✅ Token for "remember me" sessions
            $table->rememberToken();

            // ✅ Created_at and updated_at timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
