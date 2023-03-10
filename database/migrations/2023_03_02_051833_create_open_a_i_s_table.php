<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenAISTable extends Migration
{

	private $table_name = "open_ai";
    public function up()
    {
	    Schema::create($this->table_name, function (Blueprint $table) {
            $table->id();
			$table->text('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
	    Schema::dropIfExists($this->table_name);
    }
}
