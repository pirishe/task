create table users (
    id serial,
    username varchar(255) not null,
    email varchar(255) not null,
    expires_at date null default null,
    confirmed bool not null,
    checked bool not null,
    valid bool not null
);

create index on users (expires_at) where expires_at is not null and (confirmed = true or valid = true);
create index on users (expires_at) where expires_at is not null and confirmed = false and checked = false;

create table queue_emails_to_check_is_valid (
    id serial,
    user_id int not null,
    username varchar(255) not null,
    email varchar(255) not null
);
create index on queue_emails_to_check_is_valid (MOD(id, 20));

create table queue_emails_to_send (
    id serial,
    username varchar(255) not null,
    email varchar(255) not null
);
create index on queue_emails_to_check_is_valid (MOD(id, 20));
