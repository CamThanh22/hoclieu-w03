<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->foreignId('shop_id')->nullable()->constrained();
            $table->boolean('active')->default(true);
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->constrained();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained();
            $table->foreignId('product_id')->constrained();
            $table->unsignedTinyInteger('stars');
            $table->string('body', 500);
            $table->unique(['account_id', 'product_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('account_id'));
        Schema::dropIfExists('accounts');
    }
};
