<?php
declare(strict_types=1);
namespace Lab;
use PDO;
final class LocalDatabaseTools {
 public function __construct(private PDO $db) {}
 public function initialize(string $path): void { $sql=file_get_contents($path); if ($sql===false) throw new \RuntimeException('Cannot read schema'); $this->db->exec($sql); echo "Three tables initialized.\n"; }
 public function inspect(): void {
  $queries=['population'=>'SELECT COUNT(*) total_customers,SUM(marketing_opt_in=1) opted_in,SUM(marketing_opt_in=0) opted_out FROM customers','sample'=>'SELECT customer_id,full_name,email,marketing_opt_in FROM customers ORDER BY customer_id LIMIT 10','outbox'=>'SELECT campaign_id,status,COUNT(*) total,COUNT(DISTINCT customer_id) distinct_recipients FROM marketing_outbox GROUP BY campaign_id,status','eligibility'=>"SELECT COUNT(*) ineligible_recipients FROM marketing_outbox o JOIN customers c ON c.customer_id=o.customer_id WHERE o.campaign_id='CAMP-001' AND c.marketing_opt_in<>1",'history'=>'SELECT campaign_id,status,COUNT(*) total FROM sent_marketing_emails GROUP BY campaign_id,status','nonselected_outcomes'=>"SELECT COUNT(*) total FROM sent_marketing_emails h LEFT JOIN marketing_outbox o ON h.campaign_id=o.campaign_id AND h.customer_id=o.customer_id WHERE h.campaign_id='CAMP-001' AND o.request_id IS NULL"];
  foreach ($queries as $label=>$sql) echo $label.': '.json_encode($this->db->query($sql)->fetchAll(),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
 }
}
