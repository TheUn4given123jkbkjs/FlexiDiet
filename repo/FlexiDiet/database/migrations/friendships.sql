CREATE TABLE IF NOT EXISTS user_friendships (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_low_id   BIGINT UNSIGNED NOT NULL,
  user_high_id  BIGINT UNSIGNED NOT NULL,
  requester_id  BIGINT UNSIGNED NOT NULL,
  status         ENUM('pending','accepted') NOT NULL DEFAULT 'pending',
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_friendships_pair (user_low_id,user_high_id),
  KEY idx_user_friendships_low_status (user_low_id,status,updated_at),
  KEY idx_user_friendships_high_status (user_high_id,status,updated_at),
  CONSTRAINT fk_user_friendships_low FOREIGN KEY (user_low_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_friendships_high FOREIGN KEY (user_high_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_friendships_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_user_friendships_pair CHECK (user_low_id < user_high_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE notifications
  MODIFY COLUMN type ENUM('post_like','post_comment','comment_reply','friend_request','friend_accepted') NOT NULL,
  MODIFY COLUMN post_id BIGINT UNSIGNED NULL,
  ADD COLUMN target_user_id BIGINT UNSIGNED NULL AFTER comment_id,
  ADD CONSTRAINT fk_notifications_target_user
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE;
