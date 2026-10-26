# Hybrid promotional campaign laboratory

Prepared for Mark Laurence Santos, 26 October 2026.

## Current verified state

The local database contains 10,000 fictional customers (8,000 opted in, 2,000 opted out). CAMP-001 contains 50 distinct randomly selected eligible recipients with persisted PROMO15. Repeated generation and preparation preserve records and selection. Live publication passed: 50 QUEUED, and a repeat run published zero requests. SMTP processing awaits Mailtrap Sandbox settings and quota confirmation. Mock transport tests are separate from live results.

## Requirements

PHP 8.2+ with pdo_mysql, OpenSSL and mbstring; Composer; MySQL 8.0.16+ or an instructor-approved compatible database; AWS CLI; Mailtrap Email Sandbox with at least 50 remaining submissions. The workstation currently has MariaDB 10.4.32; instructor approval is still needed for this substitute.

## Installation

Run Composer with the zip extension enabled if necessary:

    composer install
    composer dump-autoload

Create a local utf8mb4 database named marketing_lab, set DB_HOST, DB_PORT, DB_NAME, DB_USER and DB_PASSWORD in the process environment, then run:

    php init-db.php
    php seed-customers.php
    php prepare-campaign.php CAMP-001 PROMO15
    php inspect.php

The configured working database is local on 127.0.0.1:3307. Its data directory is outside this source folder in the chat's work/database directory. Do not upload it.

## AWS

Region: ap-southeast-2.
Main queue: MarketingEmailQueue-mark-laurence-santos.
DLQ: MarketingEmailDLQ-mark-laurence-santos.
Application user: marketing-lab-mark-laurence-santos.
Policy: MarketingLabSQS-mark-laurence-santos.

The main queue uses 180-second visibility, 4-day retention, 20-second long polling and maxReceiveCount 3. The DLQ uses 14-day retention. The application policy permits only SendMessage, ReceiveMessage, DeleteMessage, GetQueueAttributes and GetQueueUrl on this pair. Use an approved setup identity for IAM, queue administration, CloudWatch and SNS.

Configure an AWS profile named marketing-lab with your approved credential method, set AWS_PROFILE=marketing-lab, AWS_REGION=ap-southeast-2 and SQS_QUEUE_URL to your main queue URL. Never commit credentials. In this workstation session the approved key is protected with Windows DPAPI outside the repository and loaded into environment variables by work/load-lab-environment.ps1.

## Mailtrap Sandbox

Set MAILTRAP_HOST=sandbox.smtp.mailtrap.io, MAILTRAP_PORT to the STARTTLS port from the sandbox, MAILTRAP_USERNAME and MAILTRAP_PASSWORD to the sandbox credentials, MAIL_FROM_ADDRESS=marketing@example.com and MAILTRAP_DELAY_SECONDS=11. Certificate verification remains enabled. Do not use production SMTP or real recipients.

Confirm quota before sending. Use exactly one worker. This implementation waits at least 11 seconds after each successful submission.

## Live operation

Capture the empty-queue monitoring baseline first. Then:

    php enqueue.php
    php enqueue.php
    php worker.php

The second publisher run sends no QUEUED rows. worker.php optionally accepts a maximum runtime in seconds. Stop the worker using Ctrl+C. inspect.php shows population, outbox, consent and outcome counts.

For the required baseline verify 50 SENT rows, correct promo, zero nonselected outcomes, sent-before-deleted logs, empty main queue and representative Mailtrap captures. SENT means SMTP acceptance, not actual inbox delivery.

## Failure exercise (after baseline)

Send {"campaignId":"CAMP-BROKEN","subject":"Test","body":"Missing IDs"} to the main queue. Keep one worker running, allow three 180-second visibility intervals plus redrive/metric delay, and preserve processing_failed events. Do not acknowledge the invalid message. Inspect its DLQ body and alarm transition.

Correct the missing requestId, customerId and promoCode under CAMP-RECOVERY using CUST-08001, an opted-out customer. Use a new 32-character hexadecimal requestId. The expected result is SKIPPED, followed by deletion and no SMTP call. Delete the original malformed DLQ message only after recovery is verified, then capture zero DLQ backlog and alarm OK.

## Architecture and completion map

Database connects PDO; Settings reads environment configuration; LocalDatabaseTools initializes and inspects tables; CustomerSeeder uses injected Faker; CampaignPreparer persists a random sample transactionally; QueuePublisher uploads PENDING requests; EmailSender handles Sandbox SMTP; EmailHistory stores latest outcomes; MarketingProcessor validates, checks history and consent, and coordinates sending; QueueWorker receives, processes and deletes; ActivityLogger appends allowlisted JSON metadata.

Guide tasks implemented: D1; S1-S2; C1-C3; E1-E2; Q1-Q2; P1-P5; W1-W2; M1-M3. This is an independent implementation because no starter source files were supplied. The original starter TODO locations cannot be audited until those files are provided.

## Operational limits

SQS Standard is at least once. SMTP acceptance followed by a crash before history storage can cause another submission. SQS acceptance followed by a local update failure can cause republishing. Terminal SENT/SKIPPED checks suppress sequential duplicates but do not provide exactly once delivery; multiple workers can race.

No customer name/address enters the SQS payload. JSON logs contain only approved identifiers and safe error metadata. The schema intentionally has no customer foreign key on history so unknown customers can be recorded as SKIPPED. A PENDING job is marked QUEUED only after acknowledgment; a received job is deleted only after processing and local history storage succeed.

## Monitoring

cloudwatch-dashboard.json defines separate one-minute operation-count (Sum), backlog (Maximum), age (Maximum) and DLQ-backlog (Maximum) widgets. DLQ alarm: >=1 visible message; backlog alarm: >=300 seconds; both one period, missing data not breaching. SNS setup requires an approved address and confirmation; it is currently pending.

## Submission

Publish source, composer.json/lock, schema.sql (definitions only), .gitignore, iam-policy.json and this README to your own sam-hybrid-promo-lab repository. Exclude vendor/, logs/, credentials, actual environment files, database files, customer exports and the sibling evidence directory. Review all staged files before committing.

The evidence report tracks completed and pending checks without claiming mock transport results as live SQS/Mailtrap outcomes. Add the repository URL and final live evidence to the Google Doc before instructor submission.

Repository: https://github.com/Kishhh19/Processing-Queue-Messages-using-SQS
