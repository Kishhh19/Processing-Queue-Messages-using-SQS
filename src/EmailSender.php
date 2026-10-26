<?php
declare(strict_types=1);
namespace Lab;
use PHPMailer\PHPMailer\PHPMailer;
class EmailSender {
 public function __construct(private array $settings) {}
 public function send(array $customer,array $job): string {
  if (!str_ends_with(strtolower($customer['email']),'@example.com')) throw new \RuntimeException('Fictional recipients only');
  $mail=new PHPMailer(true); $mail->isSMTP(); $mail->Host=$this->settings['host']; $mail->Port=$this->settings['port'];
  $mail->SMTPAuth=true; $mail->Username=$this->settings['username']; $mail->Password=$this->settings['password'];
  $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS; $mail->Timeout=45; $mail->CharSet='UTF-8';
  $mail->setFrom($this->settings['from'],'Laboratory Marketing'); $mail->addAddress($customer['email'],$customer['full_name']);
  $mail->Subject=$job['subject']; $mail->isHTML(false);
  $mail->Body="Hello {$customer['full_name']},\n\n{$job['body']}\n\nPromo code: {$job['promoCode']}\n\nLaboratory sandbox demonstration.";
  $mail->send(); return $mail->getLastMessageID();
 }
}
