<?php

$workerNumber = (int)$argv[1];
$workersTotalCount = (int)$argv[2];

$conn = pg_connect('host=localhost port=5432 dbname=service');

function send_email(string $from, string $to, string $text): void
{
    // заглушка
}

for (;;) {
    $sqlGetRecord = <<<SQL
SELECT id, username, email
FROM queue_emails_to_send
WHERE (id % $workersTotalCount) = $workerNumber
LIMIT 1
SQL;
    $result = pg_query($conn, $sqlGetRecord);
    $row = pg_fetch_row($result);
    if (! $row) {
        break;
    }

    $text = <<<TEXT
{$row['username']}, your subscription is expiring soon
TEXT;
    send_email('noreply@company.com', $row['email'], $text);
    pg_query($conn, 'DELETE FROM queue_emails_to_send WHERE id = ' . $row['id']);
}
