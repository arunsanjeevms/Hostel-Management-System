<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$mail = new PHPMailer(true);
$email='927623bcs011@mkce.ac.in'; //to address
$otp=rand(100000,999999);
$name='ARUN SANJEEV';
$mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'msarunsanjeev@gmail.com'; // Your Gmail email address
        $mail->Password = 'dgvq bhai wjcp tzls'; // Your Gmail password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender and recipient
        $mail->setFrom('msarunsanjeev@gmail.com', 'AFDC Secure Login System');
        $mail->addAddress($email);

        // Email content
        $mail->Subject = 'OTP for AFDC Secure Login System';
        $mail->isHTML(true);

        $year   = date("Y");
        $sentAt = date("Y-m-d H:i:s");
        
        $mail->Body = '
        <div style="margin:0; padding:0; background-color:#f4f6f8; font-family:Poppins, Arial, sans-serif; color:#333;">
          <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
              <td align="center" style="padding:20px 0;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:600px; background:#fff; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.12); overflow:hidden;">
                  
                  <!-- Header -->
                  <tr>
                    <td align="center" style="background:#4CAF50; padding:15px;">
                      <h2 style="color:#fff; margin:0; font-size:20px; font-weight:600;">AFDC Secure Login Portal</h2>
                    </td>
                  </tr>
                  
                  <!-- Body -->
                  <tr>
                    <td style="padding:25px;">
                      <p style="font-size:15px; margin:0 0 15px;">Hello <strong>' . $name . '</strong>,</p>
                      <p style="font-size:15px; margin:0 0 20px;">You have requested an OTP for your account. Please use the OTP below to complete your login:</p>
                      
                      <div style="font-size:26px; font-weight:bold; color:#d32f2f; text-align:center; margin:25px 0; letter-spacing:3px;">
                        ' . $otp . '
                      </div>
                      
                      <p style="font-size:14px; line-height:1.6; margin:0 0 25px;">If you did not request an OTP to login, please notify the Project Admin immediately to secure your account.</p>
                      
                      <p style="font-size:15px; margin:0;">Thank you,<br><strong>AFDC, Namakkal</strong></p>
                    </td>
                  </tr>
                  
                  <!-- Footer -->
                  <tr>
                    <td align="center" style="background:#f0f0f0; padding:12px; font-size:12px; color:#777;">
                      © ' . $year . ' AFDC. All rights reserved. | Sent at ' . $sentAt . '
                    </td>
                  </tr>
                  
                </table>
              </td>
            </tr>
          </table>
        </div>';
        

        // Send the email
        $mail->send();

        echo 'Password has been sent to your email.';