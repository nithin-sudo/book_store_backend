<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * @since 30-sep-2021
 * 
 * This class is respnsible for sending the message to the given email id and token.
 */
class SendEmailRequest 
{

     /**
     * @param $email,$token
     * 
     * This function takes two args from the function in ForgotPasswordcontroller and successfully 
     * sends the token as a reset link to the user email id. 
     */
    public function sendEmail($email,$token)
    {
        $name = 'Nithin Krishna';
        $email = $email;
        $subject = 'Regarding your Password Reset';
        $data ="Your password Reset Link <br>".$token;
          
        $mail = new PHPMailer(true);

        try
        {                                       
            $mail->isSMTP();                                          
            $mail->Host       = env('MAIL_HOST');                        
            $mail->SMTPAuth   = true;                                  
            $mail->Username   = env('MAIL_USERNAME');                  
            $mail->Password   = env('MAIL_PASSWORD');                              
            $mail->SMTPSecure = 'tls'; 
            $mail->Port       = 587;
            $mail->setFrom(env('MAIL_USERNAME'),env('MAIL_FROM_NAME')); 
            $mail->addAddress($email,$name);
            $mail->isHTML(true);  
            $mail->Subject =  $subject;
            $mail->Body    = $data;
            $dt = $mail->send();
            sleep(3);
            
           if($dt)
                return true;
            else
                return false;

        }
        catch (Exception $e) 
        {
            return back()->with('error','Message could not be sent.');
        }
    }
}    