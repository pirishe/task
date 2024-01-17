<?php

$workerNumber = (int)$argv[1];
$workersTotalCount = (int)$argv[2];

$conn = pg_connect('host=localhost port=5432 dbname=service');

function check_email(string $email): bool
{
    // заглушка
    return true;
}

for (;;) {
    $sqlGetRecord = <<<SQL
SELECT id, user_id, username, email
FROM queue_emails_to_check_is_valid
WHERE (id % $workersTotalCount) = $workerNumber
LIMIT 1
SQL;
    $result = pg_query($conn, $sqlGetRecord);
    $row = pg_fetch_row($result);
    if (! $row) {
        break;
    }

    $isEmailValid = check_email($row['email']);
    pg_query($conn, 'BEGIN');
    pg_query($conn, 'DELETE FROM queue_emails_to_check_is_valid WHERE id = ' . $row['id']);
    $isEmailValidString = $isEmailValid ? 'true' : 'false';
    $sqlUpdateUsers = <<<SQL
UPDATE users SET checked = true, valid = $isEmailValidString WHERE id = {$row['user_id']}
SQL;
    pg_query($conn, $sqlUpdateUsers);
    if ($isEmailValid) {
        $sqlInsertQueueEmailSend = <<<SQL
INSERT INTO queue_emails_to_send (username, email) VALUES ("{$row['username']}", "{$row['email']}")
SQL;
    }
    pg_query($conn, 'COMMIT');
}
