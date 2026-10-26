<?php
declare(strict_types=1);
namespace Lab;
final class ActivityLogger {
 public function __construct(private string $path=__DIR__.'/../logs/events.jsonl') {}
 public function record(string $event,array $metadata=[]): void {
  $metadata=array_intersect_key($metadata,array_flip(['requestId','campaignId','customerId','messageId','phase','receiveCount','reason','errorClass']));
  $dir=dirname($this->path);
  if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new \RuntimeException('Cannot create logs');
  $line=json_encode(['timestamp'=>gmdate('Y-m-d\TH:i:s\Z'),'event'=>$event]+$metadata,JSON_THROW_ON_ERROR).PHP_EOL;
  if (file_put_contents($this->path,$line,FILE_APPEND|LOCK_EX)===false) throw new \RuntimeException('Cannot append log');
 }
}
