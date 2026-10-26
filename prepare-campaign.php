<?php
require __DIR__.'/config.php';
(new Lab\CampaignPreparer((new Lab\Database())->connect()))->prepare($argv[1]??'CAMP-001',$argv[2]??'PROMO15');
