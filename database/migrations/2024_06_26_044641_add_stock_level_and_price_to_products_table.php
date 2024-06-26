<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_level')->after('id');
            $table->decimal('price', 8, 2)->after('stock_level');
            $table->date('discontinued_date')->nullable()->after('price');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_level', 'price', 'discontinued_date']);
        });
    }
};
