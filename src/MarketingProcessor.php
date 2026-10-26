<?php
declare(strict_types=1);
namespace Lab;
use PDO;
final class MarketingProcessor {
 public function __construct(private PDO $db,private EmailHistory $history,private EmailSender $sender,private ActivityLogger $log,private int $delay=11) {}
 public static function validate(array $job): void {
  foreach (['requestId'=>100,'campaignId'=>64,'customerId'=>32,'promoCode'=>64,'subject'=>255,'body'=>65535] as $key=>$max) if (!isset($job[$key]) || !is_string($job[$key]) || trim($job[$key])==='' || mb_strlen($job[$key])>$max) throw new \InvalidArgumentException("Missing or invalid field: $key");
  if (!preg_match('/^[a-f0-9]{32}$/D',$job['requestId'])) throw new \InvalidArgumentException('requestId must be 32 hexadecimal characters');
  if (preg_match('/[\r\n]/',$job['subject'])) throw new \InvalidArgumentException('Invalid subject');
 }
 public function process(array $job): void {
  self::validate($job); $meta=array_intersect_key($job,array_flip(['requestId','campaignId','customerId']));
  if ($this->history->isComplete($job['campaignId'],$job['customerId'])) { $this->log->record('duplicate_skipped',$meta); return; }
  $this->log->record('processing_started',$meta);
  try {
   $q=$this->db->prepare('SELECT * FROM customers WHERE customer_id=?'); $q->execute([$job['customerId']]); $c=$q->fetch();
   if (!$c || (int)$c['marketing_opt_in']!==1) {
    $reason=$c?'Customer opted out':'Unknown customer'; $this->history->save($job,'SKIPPED',['reason'=>$reason]); $this->log->record('skipped',$meta+['reason'=>$reason]); return;
   }
   $q=$this->db->prepare('SELECT * FROM marketing_outbox WHERE campaign_id=? AND customer_id=?'); $q->execute([$job['campaignId'],$job['customerId']]); $outbox=$q->fetch();
   if (!$outbox || QueuePublisher::payload($outbox)!==$job) throw new \InvalidArgumentException('Job does not match a prepared selected request');
   $id=$this->sender->send($c,$job);
   $this->history->save($job,'SENT',['recipient_email'=>$c['email'],'provider'=>'Mailtrap Sandbox','message_id'=>$id,'sent_at'=>gmdate('Y-m-d H:i:s')]);
   $this->log->record('sent',$meta); if ($this->delay>0) sleep($this->delay);
  } catch (\Throwable $e) { $this->history->save($job,'FAILED',['reason'=>get_class($e).': processing failed; check local configuration']); throw $e; }
 }
}
