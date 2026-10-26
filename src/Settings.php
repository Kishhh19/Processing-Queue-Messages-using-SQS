<?php
declare(strict_types=1);
namespace Lab;
use Aws\Sqs\SqsClient;
final class Settings {
 public static function required(string $key): string {
  $v=getenv($key);
  if ($v===false || trim($v)==='') throw new \RuntimeException("Missing environment variable: $key");
  return $v;
 }
 public static function queue(): SqsClient { return new SqsClient(['version'=>'2012-11-05','region'=>self::required('AWS_REGION')]); }
 public static function smtp(): array {
  $host=getenv('MAILTRAP_HOST')?:'sandbox.smtp.mailtrap.io';
  if ($host!=='sandbox.smtp.mailtrap.io') throw new \RuntimeException('Mailtrap Sandbox only');
  return ['host'=>$host,'port'=>(int)(getenv('MAILTRAP_PORT')?:2525),'username'=>self::required('MAILTRAP_USERNAME'),'password'=>self::required('MAILTRAP_PASSWORD'),'from'=>getenv('MAIL_FROM_ADDRESS')?:'marketing@example.com'];
 }
}
