<?php
declare(strict_types=1);
namespace Lab;
use PDO;
final class CampaignPreparer {
 public function __construct(private PDO $db) {}
 public function prepare(string $campaign,string $promo): void {
  if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$campaign) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$promo)) throw new \InvalidArgumentException('Invalid campaign or promo');
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare('SELECT request_id,customer_id,promo_code FROM marketing_outbox WHERE campaign_id=? FOR UPDATE'); $q->execute([$campaign]); $rows=$q->fetchAll();
   if (count($rows)===50) {
    if (count(array_unique(array_column($rows,'promo_code')))!==1 || $rows[0]['promo_code']!==$promo) throw new \RuntimeException('Persisted promo mismatch');
    $this->db->commit(); echo "Existing 50 recipients and promo preserved.\n"; return;
   }
   if ($rows) throw new \RuntimeException('Partial campaign: expected zero or 50 requests');
   $customers=$this->db->query('SELECT customer_id FROM customers WHERE marketing_opt_in=1 ORDER BY RAND() LIMIT 50')->fetchAll();
   if (count($customers)!==50) throw new \RuntimeException('Fewer than 50 eligible customers');
   $q=$this->db->prepare('INSERT INTO marketing_outbox(request_id,campaign_id,customer_id,promo_code,subject,body,created_at) VALUES(?,?,?,?,?,?,?)');
   foreach ($customers as $c) $q->execute([bin2hex(random_bytes(16)),$campaign,$c['customer_id'],$promo,'Laboratory promotion',"You have been selected to receive our promotional offer. Promo code: $promo",gmdate('Y-m-d H:i:s')]);
   $this->db->commit(); echo "Prepared 50 distinct opted-in recipients for $campaign.\n";
  } catch (\Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
 }
}
