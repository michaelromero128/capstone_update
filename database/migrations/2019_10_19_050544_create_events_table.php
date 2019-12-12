<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_title',255);
            $table->text('event_details');
            $table->string('host_organization',255);
            $table->string('event_coordinator_name');
            $table->string('event_coordinator_phone');
            $table->string('event_coordinator_email');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->string('start_time');
            $table->string('end_time');
            $table->string('requirements_major',255);
            $table->integer('age_requirement');
            $table->integer('minimum_hours');
            $table->text('tags');
            $table->boolean('featured');
            $table->string('category');
            $table->string('shifts');
            $table->string('city',255);
            $table->string('address',255);
            $table->integer('zipcode');
            $table->decimal('lat',8,5);
            $table->decimal('lon',8,5);
            $table->bigInteger('user_id')->unsigned()->nullable(true)->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
            $table->softDeletes();
            
        });

        DB::statement('ALTER TABLE events ADD FULLTEXT fulltext_index (tags, event_details, host_organization, event_title, requirements_major, category)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
