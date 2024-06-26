<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_name')->after('id');
            $table->string('product_desc')->after('product_name');
            $table->string('product_code')->nullable()->after('product_desc');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_name','product_desc','product_code']);
        });
    }
};
