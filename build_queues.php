<?php

$conn = pg_connect('host=localhost port=5432 dbname=service');

(function (resource $conn) {
    /**
     * Проверяем, что очереди обработаны. Если нет, падаем с фатальной ошибкой.
     * У нас должны быть настроены мониторинги и алерты, и мы должны были раньше
     * отреагировать на проблему и накинуть больше воркеров.
     * Поэтому эти фаталы никогда не будут падать.
     */

    $sqlQueueEmailsToSendSize = <<<SQL
SELECT count(*) cnt
FROM queue_emails_to_send
SQL;
    $result = pg_query($conn, $sqlQueueEmailsToSendSize);
    $queueEmailsToSendSize = pg_fetch_row($result)['cnt'];
    if ($queueEmailsToSendSize > 0) {
        throw new \RuntimeException('Не успели обработать очередь queue_emails_to_send');
    }
    // сбрасываю sequence
    pg_query($conn, 'TRUNCATE TABLE queue_emails_to_send RESTART IDENTITY');

    $sqlQueueEmailsToCheckIsValid = <<<SQL
SELECT count(*) cnt
FROM queue_emails_to_check_is_valid
SQL;
    $result = pg_query($conn, $sqlQueueEmailsToCheckIsValid);
    $sqlQueueEmailsToCheckIsValid = pg_fetch_row($result)['cnt'];
    if ($sqlQueueEmailsToCheckIsValid > 0) {
        throw new \RuntimeException('Не успели обработать очередь queue_emails_to_check_is_valid');
    }
    // сбрасываю sequence
    pg_query($conn, 'TRUNCATE TABLE queue_emails_to_check_is_valid RESTART IDENTITY');
})($conn);

(function (resource $conn) {
    /**
     * Заполняем очередь на отправку писем.
     */

    $sqlSubscriptionsExpireIn1Day = <<<SQL
INSERT INTO queue_emails_to_send (username, email)
SELECT username, email
FROM users
WHERE expires_at = (now() + interval '1 day')::date
    AND expires_at is not null
    AND (confirmed = true
        OR valid = true
    )
SQL;
    pg_query($conn, $sqlSubscriptionsExpireIn1Day);

    $sqlSubscriptionsExpireIn3Days = <<<SQL
INSERT INTO queue_emails_to_send (username, email)
SELECT username, email
FROM users
WHERE expires_at = (now() + interval '3 day')::date
    AND expires_at is not null
    AND (confirmed = true
        OR valid = true
    )
SQL;
    pg_query($conn, $sqlSubscriptionsExpireIn3Days);
})($conn);

(function (resource $conn) {
    /**
     * Заполняем очередь на валидацию емэйлов.
     */

    $sqlSubscriptionsExpireIn1Day = <<<SQL
INSERT INTO queue_emails_to_check_is_valid (user_id, username, email)
SELECT id, username, email
FROM users
WHERE expires_at = (now() + interval '1 day')::date
    AND expires_at is not null
    AND confirmed = false
    AND checked = false
SQL;
    pg_query($conn, $sqlSubscriptionsExpireIn1Day);

    $sqlSubscriptionsExpireIn3Days = <<<SQL
INSERT INTO queue_emails_to_check_is_valid (user_id, username, email)
SELECT id, username, email
FROM users
WHERE expires_at = (now() + interval '3 day')::date
    AND expires_at is not null
    AND confirmed = false
    AND checked = false
SQL;
    pg_query($conn, $sqlSubscriptionsExpireIn3Days);
})($conn);