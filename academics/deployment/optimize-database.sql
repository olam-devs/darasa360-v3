-- OLAM Database Optimization Script
-- Run this on all tenant databases for better performance
-- Usage: mysql -u username -p database_name < optimize-database.sql

-- ============================================================================
-- Add Indexes for Performance
-- ============================================================================

-- Students table indexes
ALTER TABLE students ADD INDEX idx_class_id (class_id);
ALTER TABLE students ADD INDEX idx_registration_no (registration_no);
ALTER TABLE students ADD INDEX idx_status (status);
ALTER TABLE students ADD INDEX idx_created_at (created_at);

-- SchoolUsers table indexes
ALTER TABLE schoolUsers ADD INDEX idx_role_id (role_id);
ALTER TABLE schoolUsers ADD INDEX idx_registration_no (registration_no);
ALTER TABLE schoolUsers ADD INDEX idx_is_active (is_active);
ALTER TABLE schoolUsers ADD INDEX idx_email (email);

-- Exam marks indexes
ALTER TABLE exam_marks ADD INDEX idx_exam_id (exam_id);
ALTER TABLE exam_marks ADD INDEX idx_student_id (student_id);
ALTER TABLE exam_marks ADD INDEX idx_subject_id (subject_id);
ALTER TABLE exam_marks ADD INDEX idx_exam_student (exam_id, student_id);
ALTER TABLE exam_marks ADD INDEX idx_exam_subject (exam_id, subject_id);

-- Exams table indexes
ALTER TABLE exams ADD INDEX idx_start_date (start_date);
ALTER TABLE exams ADD INDEX idx_end_date (end_date);
ALTER TABLE exams ADD INDEX idx_created_at (created_at);

-- Attendance indexes
ALTER TABLE attendance_entries ADD INDEX idx_student_id (student_id);
ALTER TABLE attendance_entries ADD INDEX idx_attendance_id (attendance_id);
ALTER TABLE attendance_entries ADD INDEX idx_date (date);

-- Classes indexes
ALTER TABLE classes ADD INDEX idx_name (name);
ALTER TABLE classes ADD INDEX idx_class_teacher_id (class_teacher_id);

-- Subjects indexes
ALTER TABLE subjects ADD INDEX idx_name (name);

-- Teacher relationships
ALTER TABLE subject_teacher ADD INDEX idx_teacher_id (teacher_id);
ALTER TABLE subject_teacher ADD INDEX idx_subject_id (subject_id);
ALTER TABLE subject_teacher ADD INDEX idx_class_id (class_id);
ALTER TABLE class_teacher ADD INDEX idx_teacher_id (teacher_id);
ALTER TABLE class_teacher ADD INDEX idx_class_id (class_id);

-- Announcements indexes
ALTER TABLE announcements ADD INDEX idx_created_at (created_at);
ALTER TABLE announcements ADD INDEX idx_class_id (class_id);

-- SMS messages indexes
ALTER TABLE sms_messages ADD INDEX idx_created_at (created_at);
ALTER TABLE sms_messages ADD INDEX idx_status (status);
ALTER TABLE sms_messages ADD INDEX idx_class_id (class_id);

-- Fees and payments indexes
ALTER TABLE fees ADD INDEX idx_class_id (class_id);
ALTER TABLE fees ADD INDEX idx_term (term);
ALTER TABLE student_fees ADD INDEX idx_student_id (student_id);
ALTER TABLE student_fees ADD INDEX idx_fee_id (fee_id);
ALTER TABLE student_fees ADD INDEX idx_status (status);
ALTER TABLE payments ADD INDEX idx_student_id (student_id);
ALTER TABLE payments ADD INDEX idx_created_at (created_at);
ALTER TABLE invoices ADD INDEX idx_student_id (student_id);
ALTER TABLE invoices ADD INDEX idx_status (status);

-- Support tickets indexes
ALTER TABLE support_tickets ADD INDEX idx_status (status);
ALTER TABLE support_tickets ADD INDEX idx_priority (priority);
ALTER TABLE support_tickets ADD INDEX idx_created_at (created_at);
ALTER TABLE support_tickets ADD INDEX idx_assigned_to (assigned_to);

-- Report cards indexes
ALTER TABLE report_cards ADD INDEX idx_student_id (student_id);
ALTER TABLE report_cards ADD INDEX idx_config_id (config_id);
ALTER TABLE report_cards ADD INDEX idx_created_at (created_at);

-- Notification queue indexes
ALTER TABLE notification_queue ADD INDEX idx_status (status);
ALTER TABLE notification_queue ADD INDEX idx_created_at (created_at);
ALTER TABLE notification_queue ADD INDEX idx_notification_category (notification_category);

-- Logs indexes
ALTER TABLE logs ADD INDEX idx_action (action);
ALTER TABLE logs ADD INDEX idx_user_id (user_id);
ALTER TABLE logs ADD INDEX idx_created_at (created_at);

-- ============================================================================
-- Optimize Tables
-- ============================================================================

OPTIMIZE TABLE students;
OPTIMIZE TABLE schoolUsers;
OPTIMIZE TABLE exam_marks;
OPTIMIZE TABLE exams;
OPTIMIZE TABLE attendance_entries;
OPTIMIZE TABLE classes;
OPTIMIZE TABLE subjects;
OPTIMIZE TABLE announcements;
OPTIMIZE TABLE sms_messages;
OPTIMIZE TABLE fees;
OPTIMIZE TABLE student_fees;
OPTIMIZE TABLE payments;
OPTIMIZE TABLE support_tickets;
OPTIMIZE TABLE report_cards;
OPTIMIZE TABLE logs;

-- ============================================================================
-- Analyze Tables (Update Statistics)
-- ============================================================================

ANALYZE TABLE students;
ANALYZE TABLE schoolUsers;
ANALYZE TABLE exam_marks;
ANALYZE TABLE exams;
ANALYZE TABLE attendance_entries;
ANALYZE TABLE classes;
ANALYZE TABLE subjects;
ANALYZE TABLE announcements;
ANALYZE TABLE sms_messages;
ANALYZE TABLE fees;
ANALYZE TABLE student_fees;
ANALYZE TABLE payments;
ANALYZE TABLE support_tickets;
ANALYZE TABLE report_cards;
ANALYZE TABLE logs;

-- ============================================================================
-- Clean up old data (Optional - uncomment if needed)
-- ============================================================================

-- Delete logs older than 90 days
-- DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete old SMS messages (older than 1 year)
-- DELETE FROM sms_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Archive old exams (older than 2 years) - Move to archive table instead of deleting
-- CREATE TABLE IF NOT EXISTS exams_archive LIKE exams;
-- INSERT INTO exams_archive SELECT * FROM exams WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);
-- DELETE FROM exams WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 YEAR);

SELECT 'Database optimization completed successfully!' AS Status;
