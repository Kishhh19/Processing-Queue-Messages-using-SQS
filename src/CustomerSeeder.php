<?php
declare(strict_types=1);
namespace Lab;
use PDO;
use Faker\Generator;
final class CustomerSeeder {
 public function __construct(private PDO $db,private Generator $faker) {}
 public function seed(): void {
  $q=$this->db->prepare('INSERT INTO customers(customer_id,full_name,email,marketing_opt_in,created_at) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE customer_id=VALUES(customer_id)');
  $this->db->beginTransaction();
  try {
   for ($i=1;$i<=10000;$i++) $q->execute([sprintf('CUST-%05d',$i),$this->faker->name(),sprintf('customer%05d@example.com',$i),$i<=8000?1:0,gmdate('Y-m-d H:i:s')]);
   $this->db->commit();
  } catch (\Throwable $e) { $this->db->rollBack(); throw $e; }
  echo "Customer generation complete; existing records preserved.\n";
 }
}
