<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    //user registration success test
    public function test_IfGiven_UserCredentials_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/auth/register', [
            "firstname" => "Niraj",
            "lastname" => "Krishna",
            "email" => "nda3778@gmail.com",
            "mobile"=> "9685741236",
            "password" => "nithin@123",
            "confirm_password" => "nithin@123"
        ]);
        $response->assertStatus(201)->assertJson(['message' => 'User successfully registered']);
    }

    //user registration Error test
    public function test_IfGiven_UserCredentialsSame_ShouldValidate_AndReturnErrorStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/auth/register', 
        [
            "firstname" => "Niraj",
            "lastname" => "Krishna",
            "email" => "nda3778@gmail.com",
            "mobile"=> "9685741236",
            "password" => "nithin@123",
            "confirm_password" => "nithin@123"
        ]);
        $response->assertStatus(401)->assertJson(['message' => 'Both mobile number and email has already been taken']);
    }

    public function test_IfGiven_LoginCredentials_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/auth/login', 
        [
            "email" => "nda3778@gmail.com",
            "password" => "nithin@123",
        ]);
        $response->assertStatus(200)->assertJson(['message' => 'Login successfull']);
    }

      //login error status
      public function test_IfGiven_NotRegistered_LoginCredentials_ShouldValidate_AndReturnErrorStatus()
      {
          $response = $this->withHeaders([
              'Content-Type' => 'Application/json',
          ])->json('POST', '/api/auth/login', 
          [
              "email" => "abc@gmail.com",
              "password" => "simba@123",
          ]);
  
          $response->assertStatus(401)->assertJson(['message' => 'we can not find the user with that e-mail address You need to register first']);
      }

    //forgot passsword success
    public function test_IfGiven_Registered_EmailId_ShouldValidate_AndReturnSuccessStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/auth/forgotpassword', [
            "email" => "nda3778@gmail.com"
        ]);
        $response->assertStatus(200)->assertJson(['message'=> 'we have mailed your password reset link to respective E-mail']);
    }

    //forgot password failure
    public function test_IfGiven_WrongEmailId_ShouldValidate_AndReturnErrorStatus()
    {
        $response = $this->withHeaders([
            'Content-Type' => 'Application/json',
        ])->json('POST', '/api/auth/forgotpasssword', 
        [
            "email" => "nkrishna@gmail.com",
        ]);
        $response->assertStatus(404)->assertJson(['message' => 'we can not find a user with that email address']);
    } 
}
