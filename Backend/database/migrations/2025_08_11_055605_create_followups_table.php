<?php

use App\Models\Receiver;
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
        Schema::create('followups', function (Blueprint $table) {
            $table->bigIncrements('task_id'); // custom PK
            $table->string('title', 50); // required
            // $table->unsignedBigInteger('creator_user_id')->nullable(); // FK to users.user_id
            $table->unsignedBigInteger('creator_receiver_id')->nullable(); // FK to receivers.receiver_id
            $table->text('description')->nullable();
            $table->string('status')->default('Pending');
            $table->date('date'); //Date
            $table->boolean('set_reminder')->default(false); // default false
            $table->time('time')->default('08:00:00'); // default time
            // 🆕 Tracking columns
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps(); // Adds created_at and updated_at


            // foreign key constraint
            // $table->foreign('creator_user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('creator_receiver_id')->references('receiver_id')->on('receiver')->onDelete('cascade');

            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followups');
    }
};
