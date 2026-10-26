<?php
declare(strict_types=1);
namespace Lab;
use PDO;
final class EmailHistory {
 public function __construct(private PDO $db) {}
 public function isComplete(string $campaign,string $customer): bool {
  $q=$this->db->prepare('SELECT status FROM sent_marketing_emails WHERE campaign_id=? AND customer_id=?'); $q->execute([$campaign,$customer]);
  return in_array($q->fetchColumn(),['SENT','SKIPPED'],true);
 }
 public function save(array $job,string $status,array $d=[]): void {
  $q=$this->db->prepare('INSERT INTO sent_marketing_emails(campaign_id,customer_id,request_id,promo_code,recipient_email,subject,status,provider,message_id,reason,processed_at,sent_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE request_id=VALUES(request_id),promo_code=VALUES(promo_code),recipient_email=VALUES(recipient_email),subject=VALUES(subject),status=VALUES(status),provider=VALUES(provider),message_id=VALUES(message_id),reason=VALUES(reason),processed_at=VALUES(processed_at),sent_at=VALUES(sent_at)');
  $q->execute([$job['campaignId'],$job['customerId'],$job['requestId'],$job['promoCode'],$d['recipient_email']??null,$job['subject'],$status,$d['provider']??null,$d['message_id']??null,$d['reason']??null,gmdate('Y-m-d H:i:s'),$d['sent_at']??null]);
 }
}
