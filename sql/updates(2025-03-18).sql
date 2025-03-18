alter table users
add column gender enum('M','F', 'U') default 'U'
after last_name