-- Run ONCE after schema.sql. Repeatable inserts by exercise code.
USE flexidiet;
INSERT INTO exercise_types (code,name_vi,uses_distance) VALUES
('running','Chạy bộ',1),('cycling','Đạp xe',1),('gym','Tập gym',0),
('swimming','Bơi lội',0),('hiit','HIIT',0),('jump_rope','Nhảy dây',0),
('walking','Đi bộ',1),('yoga','Yoga',0),('badminton','Cầu lông',0),('football','Bóng đá',0)
ON DUPLICATE KEY UPDATE name_vi=VALUES(name_vi);
-- These are MVP example intensities; refine/attribute from a validated MET reference for the report.
INSERT INTO exercise_met_rules (exercise_type_id,met,source_ref)
SELECT t.id, v.met, 'demo-default'
FROM exercise_types t
JOIN (
 SELECT 'running' code, 8.5 met UNION ALL SELECT 'cycling',7.5 UNION ALL
 SELECT 'gym',5.0 UNION ALL SELECT 'swimming',8.0 UNION ALL
 SELECT 'hiit',10.0 UNION ALL SELECT 'jump_rope',11.0 UNION ALL
 SELECT 'walking',3.5 UNION ALL SELECT 'yoga',2.8 UNION ALL
 SELECT 'badminton',6.0 UNION ALL SELECT 'football',9.0
) v ON v.code=t.code
WHERE NOT EXISTS (SELECT 1 FROM exercise_met_rules r WHERE r.exercise_type_id=t.id);
