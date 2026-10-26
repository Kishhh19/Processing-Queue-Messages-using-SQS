<?php
declare(strict_types=1);
namespace Lab;
use Aws\Sqs\SqsClient;
final class QueueWorker {
 public function __construct(private SqsClient $sqs,private string $url,private MarketingProcessor $processor,private ActivityLogger $log) {}
 public function run(int $maxSeconds=0): void {
  $start=time(); do {
   $result=$this->sqs->receiveMessage(['QueueUrl'=>$this->url,'MaxNumberOfMessages'=>1,'WaitTimeSeconds'=>20,'VisibilityTimeout'=>180,'MessageSystemAttributeNames'=>['ApproximateReceiveCount']]);
   foreach ($result['Messages']??[] as $message) $this->handle($message);
  } while ($maxSeconds===0 || time()-$start<$maxSeconds);
 }
 public function handle(array $message): void {
  $phase='decode'; $meta=['messageId'=>$message['MessageId'],'receiveCount'=>(int)($message['Attributes']['ApproximateReceiveCount']??0)]; $this->log->record('received',$meta);
  try {
   $job=json_decode($message['Body'],true,512,JSON_THROW_ON_ERROR);
   if (!is_array($job) || array_is_list($job)) throw new \InvalidArgumentException('Job must be an object');
   MarketingProcessor::validate($job); $meta+=array_intersect_key($job,array_flip(['requestId','campaignId','customerId']));
   $phase='process'; $this->processor->process($job);
   $phase='delete'; $this->sqs->deleteMessage(['QueueUrl'=>$this->url,'ReceiptHandle'=>$message['ReceiptHandle']]);
   $this->log->record('deleted',$meta+['phase'=>$phase]); echo "DELETED {$message['MessageId']}\n";
  } catch (\Throwable $e) { $this->log->record($phase==='delete'?'ack_failed':'processing_failed',$meta+['phase'=>$phase,'errorClass'=>get_class($e)]); echo "FAILED $phase {$message['MessageId']} ".get_class($e)."\n"; }
 }
}
