CREATE table petition_number(
    name varchar(255) not null primary key,
    number int default 0
);

alter table petition_number
    OWNER TO cts;

INSERT INTO petition_number(name, number) VALUES ('petition_number', 0);

