<?php
declare(strict_types=1);
namespace Lab;
use PDO;
use Aws\Sqs\SqsClient;
final class QueuePublisher {
 public function __construct(private PDO $db,private SqsClient $sqs,private string $url,private ActivityLogger $log) {}
 public static function payload(array $r): array { return ['requestId'=>$r['request_id'],'campaignId'=>$r['campaign_id'],'customerId'=>$r['customer_id'],'promoCode'=>$r['promo_code'],'subject'=>$r['subject'],'body'=>$r['body']]; }
 public function publishPending(): void {
  $rows=$this->db->query("SELECT * FROM marketing_outbox WHERE status='PENDING' ORDER BY created_at,request_id")->fetchAll();
  $q=$this->db->prepare("UPDATE marketing_outbox SET status='QUEUED',queued_at=?,sqs_message_id=? WHERE request_id=? AND status='PENDING'");
  foreach ($rows as $row) {
   $job=self::payload($row); $meta=array_intersect_key($job,array_flip(['requestId','campaignId','customerId']));
   try {
    $result=$this->sqs->sendMessage(['QueueUrl'=>$this->url,'MessageBody'=>json_encode($job,JSON_THROW_ON_ERROR)]);
    $id=(string)$result['MessageId']; if ($id==='') throw new \RuntimeException('Missing acknowledgment ID');
    $q->execute([gmdate('Y-m-d H:i:s'),$id,$row['request_id']]);
    $this->log->record('queued',$meta+['messageId'=>$id]); echo "QUEUED {$job['requestId']} $id\n";
   } catch (\Throwable $e) { $this->log->record('enqueue_failed',$meta+['phase'=>'publish','errorClass'=>get_class($e)]); throw $e; }
  }
  echo count($rows)." pending requests published.\n";
 }
}
