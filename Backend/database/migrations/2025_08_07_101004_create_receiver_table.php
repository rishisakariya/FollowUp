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
        Schema::create('receiver', function (Blueprint $table) {
            $table->bigIncrements('receiver_id'); // custom PK
            $table->string('name', 50); // required
            // $table->unsignedBigInteger('creator'); // FK to users.user_id
            $table->text('color');

            // Foreign key constraint
            // $table->foreign('creator')->references('user_id')->on('users')->onDelete('cascade');

            // Tracking fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps(); // created_at, updated_at

            // Foreign key constraints
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receiver');
    }
};
