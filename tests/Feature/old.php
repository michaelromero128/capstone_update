<?php

namespace Tests\Feature;


use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Tests\TestCase;
use App\EventPhoto;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Mail\Resend;
use App\Mail\Verify;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;
use \Mockery;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Http\UploadedFile;
use App\Notifications\CustomResetPasswordNotification;




class old extends TestCase
{
    
    /**
     * A basic test example.
     *
     * @return void
     */
    use DatabaseTransactions;
    public function testRegister()
    {
        Mail::fake();
        $this->createTestUserParams();
        $response = $this->post(route('register'),$this->user_params);
        $response->assertOk();
        $user = User::where('email',config('authController.test_email'))->first();
        if($user){
            $this->assertEquals('test',$user->title);
            $this->assertEquals($user->name,'test');
            $this->assertEquals($user->location,'test');
            $this->assertEquals($user->phone, 'test');
            $this->assertEquals($user->email,config('authController.test_email'));
            $this->assertEquals($user->rank,'reg');
            $this->assertTrue(Hash::check('testtest', $user->password));
            $this->assertNotNull($user->verifyUser);
            $this->assertNull($user->email_verified_at);
            Mail::assertQueued(Verify::class, function ($mail) use($user){
                
                return $mail->hasTo($user->email);
            });
        }else{
            $this->fail('should find a user');
        }
    }
    
    
    public function testBadRegister(){
        Mail::fake();
        $this->createBadTestUserParams();
        $response = $this->post(route('register'),$this->bad_user_params);
        $response->assertStatus(422);
        Mail::assertNothingQueued();
    }
    
    public function  testResend()
    {
        Mail::fake();
        $this->createTestUserParams();
        $response = $this->post(route('register'),$this->user_params);
        $response->assertOk();
        $user = User::where('email',config('authController.test_email'))->first();
        if($user){
            $response = $this->post(route('email.customResend'), ['email' => $user->email]);
            $response->assertOk();
            Mail::assertQueued(Resend::class);
        }else{
            $this->fail('should find a user');
        }
    }
    
    public function testBadResend(){
        Mail::fake();
        $response = $this->post(route('email.customResend'), ['email' => 'bad@bad.bad']);
        $response->assertStatus(404);
        Mail::assertNothingQueued();
        
    }
    public function testChangePassword(){
        $this->withoutMiddleware();
        $user =$this->createVerifiedUser();
        $response = $this->post(route('password.change'),[
            'email' => $user->email,
            'current_password' => 'testtest',
            'new_password' => 'thingathing',
            'new_password_confirmation' => 'thingathing'
            
        ]);
        
        $response->assertOk();
        $user->refresh();
        $this->assertTrue(Hash::check('thingathing', $user->password));
    }
    public function  testForgotPassword(){
        Mail::fake();
        Notification::fake();
        $this->withoutMiddleWare();
        $user =$this->createVerifiedUser();
        $response = $this->post(route('password.email'),['email' =>$user->email]);
        $response->assertOk();
        
        Notification::assertSentTo([$user], CustomResetPasswordNotification::class, function($notification, $channels) use ($user){
            $response = $this->post(route('password.update'), ['email' => $user->email, 'password'=> 'thingathing', 'password_confirmation' => 'thingathing', 'token' => 'allegory']);
            $response->assertStatus(400);
            $response = $this->post(route('password.update'), ['email' => $user->email, 'password'=> 'thingathing', 'password_confirmation' => 'thingathing', 'token' => $notification->token]);
            $user->refresh();
            $this->assertTrue(Hash::check('thingathing', $user->password));
           return true;
        });
    }
    
    
    public function testVerify(){
        $this->createTestUserParams();
        $response = $this->post(route('register'), $this->user_params);
        $response->assertOk();
        $user = User::where('email',config('authController.test_email'))->first();
        if($user){ 
            $token = $user->verifyUser->token;
            $id = $user->verifyUser->user_id;
            $response2 = $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
            $response2->assertOk();
            $user = $user->refresh();
            $this->assertNotNull($user->email_verified_at); 
        }else{
            $this->fail('should find a user');
        }
    }
    public function testBadVerify(){
        $this->createTestUserParams();
        $response = $this->post(route('register'), $this->user_params);
        $response->assertOk();
        $user = User::where('email',config('authController.test_email'))->first();
        if($user){
            $token = 'abcd';
            $id = $user->verifyUser->user_id;
            $response2 = $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
            $response2->assertStatus(400);
            $user = $user->refresh();
            $this->assertNull($user->email_verified_at);
        }else{
            $this->fail('should find a user');
        }
    }
    public function testGetAuthToken(){
        $user =$this->createVerifiedUser();
        $params = [
          'email' =>$user->email,
          'password' =>'testtest',
        ];
        $out = new Response(200);//
       
        $client = Mockery::mock(Client::class);
        $client->makePartial()->shouldReceive('request')->once()->andReturn($out);
        $this->app->instance(Client::class, $client);
        $response = $this->post(route('auth_grant'), $params);
        $response->assertOk();
        \Mockery::close();
    }
    
    public function testBadGetAuthToken(){
        $user =$this->createVerifiedUser();
        $params = [
            'email' =>$user->email,
            'password' =>'testatest',
        ];
        $out = new Response(400);//
        
        $client = Mockery::mock(Client::class);
        $client->makePartial()->shouldReceive('request')->once()->andReturn($out);
        $this->app->instance(Client::class, $client);
        $response = $this->post(route('auth_grant'), $params);
        $response->assertStatus(401);
        // 401 because the route will throw a authorization exception if anything but a 200 is returned by the guzzle client
        \Mockery::close();
    }
    
    public function testCreateEvent(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $response->assertOk()->assertJson([
            'event_title' => 'test',
            'event_description' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test@test.test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11',
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'requirements_year' => 'test',
            'requirement_one' => 'test',
            'requirement_two' => 'test',
            'requirement_three' => 'test',
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'test',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
            'lat' => 43.00590,
            'lon' => -71.01320,
            'user_id' => $user->id,
        ]);
        $this->assertEquals('docs/' . $file->hashName(), EventPhoto::latest()->first()->filename);
        Storage::disk('public')->assertExists('docs/' .$file->hashName());     
    }
    public function testCreateBadEvent(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createBadEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $response->assertStatus(422);
    }
    public function testGetOneEvent(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $id =$response->json('id');
        $response = $this->get('api/events/' . $id);
        $response->assertOk();
        $response->assertJson([
            'event_title' => 'test',
            'event_description' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test@test.test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11',
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'requirements_year' => 'test',
            'requirement_one' => 'test',
            'requirement_two' => 'test',
            'requirement_three' => 'test',
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'test',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
        ]);
        $response = $this->get('api/events/' . -33);
        $response->assertStatus(404);
    }
    public function testGetEvents(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $response = $this->get('api/events');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response->assertJsonStructure([
            'data' => [
                '*' =>[
                    'event_title',
                    'event_description'  ,
                    'host_organization',
                    'event_coordinator_name',
                    'event_coordinator_phone',
                    'event_coordinator_email',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'requirements_major',
                    'requirements_year',
                    'requirement_one',
                    'requirement_two',
                    'requirement_three',
                    'age_requirement',
                    'minimum_hours',
                    'tags',
                    'category',
                    'shifts',
                    'city',
                    'address',
                    'zipcode',
                    'event_photos'
                    
                ],
                '*' =>[
                    'event_title',    
                    'event_description'  ,
                    'host_organization',
                    'event_coordinator_name',
                    'event_coordinator_phone',
                    'event_coordinator_email',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'requirements_major',
                    'requirements_year',
                    'requirement_one',
                    'requirement_two',
                    'requirement_three',
                    'age_requirement',
                    'minimum_hours',
                    'tags',
                    'category',
                    'shifts',
                    'city',
                    'address',
                    'zipcode',
                ]
            ]
        ]);
        
        $response = $this->get('api/events/' . -33);
        $response->assertStatus(404);
        
        // test every variant of a get with different param combinations to test each return 
        $response = $this->get('api/events?zipcode=33187&range=10000&q=test&date=2020-10-10');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?zipcode=33187&range=10000&q=test&old=yes');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?zipcode=33187&range=10000');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?q=test&date=2020-10-10');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?q=test&old=yes');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?date=2020-10-10');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        $response = $this->get('api/events?old=yes');
        $response->assertOk();
        $response->assertJsonStructure([
            'data'
        ]);
        
    }
    
    public function testPutEvent(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $id =$response->json('id');
        $params= ['event_title' => 'thing'];
        $response = $this->call('PUT', 'api/events/' . $id, $params, [],[]);
        $response->assertOk();
        $response->assertJson([
            'event_title' => 'thing',
        ]);
        $response = $this->call('PUT', 'api/events/' . -33, $params, [],[]);
        $response->assertStatus(404);
    }
    public function testDeleteEvent(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $id =$response->json('id');
        $params= ['event_title' => 'thing'];
        $response = $this->call('DELETE', 'api/events/' . $id, [], [],[]);
        $response->assertOk();
        $response = $this->call('GET', 'api/events/' . $id, [], [],[]);
        $response->assertStatus(404);
        $response = $this->call('DELETE', 'api/events/' . $id, [], [],[]);
        $response->assertStatus(404);
    }
    public function testGetPostDeletePhoto(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], []);
        $id =$response->json('id');
        $file2 = UploadedFile::fake()->image('random.jpg');
        $response = $this->call('POST', 'api/file/' . $id, $params, [], ['file' => [$file2]]);
        $response->assertStatus(200);
        
        $response->assertJsonStructure( [
                'event_title',
                'event_description'  ,
                'host_organization',
                'event_coordinator_name',
                'event_coordinator_phone',
                'event_coordinator_email',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'requirements_major',
                'requirements_year',
                'requirement_one',
                'requirement_two',
                'requirement_three',
                'age_requirement',
                'minimum_hours',
                'tags',
                'category',
                'shifts',
                'city',
                'address',
                'zipcode',
                'event_photos'
                
            ],);
        //$event_photo_array = $response->json('');
       // dd(  $file2->hashName()  .  '  |\||\\\\\\\ ' . $response->json('event_photos')[0]['filename']);
        
        $this->assertEquals('docs/' . $file2->hashName(), EventPhoto::latest()->first()->filename);
        Storage::disk('public')->assertExists('docs/' . $file2->hashName()); 
        $this->assertEquals($id,$response->json('id'));
        $photo = EventPhoto::latest()->first();
        $name=$photo->filename;
        $photoId=$photo->id;
        $eventPhotoArray= $response->json('event_photos')[0];
        $photoId = $eventPhotoArray['id'];
        $name = $eventPhotoArray['filename'];
        $response = $this->call('GET', 'api/file/' . $photoId, $params, [], []);
        $response->assertStatus(200);
        $response->assertJsonStructure(['id','filename','created_at','updated_at','event_id']);
        $response = $this->call('GET', 'api/file/' . -33, $params, [], []);
        $response->assertStatus(404);
        $response = $this->call('DELETE', 'api/file/' . $photoId, $params, [], []);
        $response->assertStatus(200);
        $response = $this->call('GET', 'api/file/' . $photoId, $params, [], []);
        $response->assertStatus(404);
        //dd($name . ' \\\\\\\\ ' .  var_dump($eventPhotoArray) . ' \\\\\\\\\ ' . $photoId );
        Storage::disk('public')->assertMissing($name); 
        
        $response = $this->call('DELETE', 'api/file/' . $photoId, $params, [], []);
        $response->assertStatus(404);  
    }
    public function testUserChange(){
        
        
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $userAdmin= User::where('rank', 'root')->first();
        
        $this->be($userAdmin);
        $response = $this->call('POST', route('user.change_rank'), ['user_id' => $user->id, 'change' => 'elevate'], [], []);
        $response->assertOk();
        $user->refresh();
        $this->assertEquals('elevated',$user->rank);
        $response = $this->call('POST', route('user.change_rank'), ['user_id' => $user->id, 'change' => 'demote'], [], []);
        $response->assertOk();
        $user->refresh();
        $this->assertEquals('reg',$user->rank);
        $this->be($user);
        $response = $this->call('POST', route('user.change_rank'), ['user_id' => $userAdmin->id, 'change' => 'demote'], [], []);
        $response->assertStatus(401);
    }
    public function testUserGets(){
        $this->withoutMiddleware();
        $user= $this->createVerifiedUser();
        $file = UploadedFile::fake()->image('random.jpg');
        $this->be($user);
        Storage::fake('public');
        $params = $this->createEventParams();
        $response = $this->call('POST', route('event.store'), $params, [], ['file' => [$file]]);
        $response2 = $this->call('GET', 'api/user/profile/' . $user->id, $params, [], []);
        $response2->assertJsonStructure(['id', 'title', 'name', 'location', 'phone', 'email']);
        $this->assertEquals($user->id, $response2->json('id'));
        $response3 = $this->call('GET', 'api/user/events/' . $user->id, $params, [], []);
        $response3->assertJsonStructure([
            'data' => [
                
                '*' =>[
                    'event_title',
                    'event_description'  ,
                    'host_organization',
                    'event_coordinator_name',
                    'event_coordinator_phone',
                    'event_coordinator_email',
                    'start_date',
                    'end_date',
                    'start_time',
                    'end_time',
                    'requirements_major',
                    'requirements_year',
                    'requirement_one',
                    'requirement_two',
                    'requirement_three',
                    'age_requirement',
                    'minimum_hours',
                    'tags',
                    'category',
                    'shifts',
                    'city',
                    'address',
                    'zipcode',
                ]
            ]
        ]);
        
        
        
        
    }
    
    
    
    
    
    
    
    
       
    private function createTestUserParams(){
        $params = [
            'title' => 'test',
            'name' => 'test',
            'location' => 'test',
            'phone' => 'test',
            'email' => config('authController.test_email'),
            'password' => 'testtest',
            'password_confirmation' => 'testtest'
        ];
        return $params;
    }
    private function createBadTestUserParams(){
        $params = [
            'title' => 'test',
            'name' => 'test',
            'location' => 'test',
            'phone' => 'test',
            'email' => config('authController.test_email'),
            'password' => 'testtest',
            'password_confirmation' => 'testatest'
        ];
        return $params;
    }
    private function createVerifiedUser(){
        $this->createTestUserParams();
        $this->post(route('register'), $this->createTestUserParams());
        $user = User::where('email',config('authController.test_email'))->first();
        $token = $user->verifyUser->token;
        $id = $user->verifyUser->user_id;
        $this->post(route('email.customVerify'), ['user_id' => $id, 'token' => $token]);
        
        $user=$user->refresh();
        return $user;
    }
    
    private function createEventParams(){
        
        $params = [
            'event_title' => 'test',
            'event_details' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test@test.test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11',
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'test',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
            
        ];
        return $params;
    }
    
    private function createBadEventParams(){
        
        $params = [
            //'event_title' => 'test',
            'event_details' => 'test',
            'host_organization' => 'test',
            'event_coordinator_name' => 'test',
            'event_coordinator_phone' => 'test',
            'event_coordinator_email' => 'test@test.test',
            'start_date' => '2020-10-10',
            'end_date' => '2020-10-11',
            'start_time' => 'test',
            'end_time' => 'test',
            'requirements_major' => 'test',
            'age_requirement' => 18,
            'minimum_hours' => 2,
            'tags' => 'test',
            'category' => 'test',
            'shifts' => 'test',
            'city' => 'test',
            'address' => 'test',
            'zipcode' => '210',
             
        ];
        return $params;
    }
    



}
