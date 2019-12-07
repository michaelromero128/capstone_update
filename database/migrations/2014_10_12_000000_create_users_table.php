<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\User;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('name');
            $table->string('location');
            $table->string('phone');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('rank');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
            $params = [
                'title' => '',
                'name' => '',
                'location' => '',
                'phone' => '',
                'email' => 'thing@example.org',
                'rank' => 'root',
                'password' => Hash::make('maxpowerdefault'),
                'email_verified_at' => now(),
                
            ];
        $user =User::create($params);
        $user->markEmailAsVerified();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
