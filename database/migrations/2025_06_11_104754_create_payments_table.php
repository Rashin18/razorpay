<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
        $table->string('razorpay_order_id')->unique();
        $table->string('razorpay_payment_id')->nullable();
        $table->string('razorpay_signature')->nullable();
        $table->integer('amount'); // in paise
        $table->string('currency')->default('INR');
        $table->string('status')->default('created'); // created, success, failed
        $table->timestamps();
    });
}

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};