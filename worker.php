<?php
require __DIR__.'/config.php';
$db=(new Lab\Database())->connect(); $log=new Lab\ActivityLogger();
$p=new Lab\MarketingProcessor($db,new Lab\EmailHistory($db),new Lab\EmailSender(Lab\Settings::smtp()),$log,max(11,(int)(getenv('MAILTRAP_DELAY_SECONDS')?:11)));
(new Lab\QueueWorker(Lab\Settings::queue(),Lab\Settings::required('SQS_QUEUE_URL'),$p,$log))->run((int)($argv[1]??0));
