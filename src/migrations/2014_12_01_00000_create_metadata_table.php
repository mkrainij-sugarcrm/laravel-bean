<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMetadataTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        //
        Schema::create(
            'field_metadatas',
            function ($table) {
                $table->bigIncrements('id');
                $table->string('parent_type', 255);
                $table->string('field_name', 255);
                $table->string('display_name', 255);
                $table->string('field_type', 255);
                $table->text('options_list')->nullable();
                $table->timestamps();
            }
        );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		//
        Schema::drop('field_metadatas');
	}

}
