<?php
require 'PHPMailerAutoload.php';

$mail = new PHPMailer;
//$mail->SMTPDebug = 2;	// Debug ON
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = 'smtp.gmail.com';                       // Specify main and backup server
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = 'rinzler.d.vicky@gmail.com';                   // SMTP username
$mail->Password = 'googleG1';               // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
$mail->Port = 587;                                    //Set the SMTP port number - 587 for authenticated TLS
$mail->setFrom('', 'Rinzler');     //Set who the message is to be sent from
//Set an alternative reply-to address
$mail->addAddress('vicky16.2012@gmail.com', 'Vicky');  // Add a recipient
$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
// Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = 'Here is the subject';
$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

//Read an HTML message body from an external file, convert referenced images to embedded,
//convert HTML into a basic plain-text alternative body


if(!$mail->send()) {
	echo "<pre>";
   echo 'Message could not be sent.';
   echo 'Mailer Error: ' . $mail->ErrorInfo;
   exit;
}

echo 'Message has been sent';