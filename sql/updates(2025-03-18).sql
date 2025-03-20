alter table users
add column gender enum('M','F', 'U') default 'U'
after last_name;

alter table meetings
add column schedule_id INT after meeting_uid,
add column attendee_pw VARCHAR(255) NULL after meeting_name, 
add column moderator_pw VARCHAR(255) NULL after attendee_pw,
add column end_time TIMESTAMP NULL;