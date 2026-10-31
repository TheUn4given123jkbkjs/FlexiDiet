-- Apply once to an existing FlexiDiet database (do not run schema.sql here).
ALTER TABLE posts
  ADD COLUMN privacy ENUM('public','private') NOT NULL DEFAULT 'public'
  AFTER content;

CREATE TABLE comments (
  id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  post_id    BIGINT UNSIGNED NOT NULL,
  user_id    BIGINT UNSIGNED NOT NULL,
  parent_id  BIGINT UNSIGNED NULL,
  content    VARCHAR(2000) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_comments_post_created (post_id, created_at, id),
  KEY idx_comments_parent (parent_id, created_at, id),
  KEY idx_comments_user_created (user_id, created_at),
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE post_shares (
  id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  source_post_id    BIGINT UNSIGNED NOT NULL,
  shared_by_user_id BIGINT UNSIGNED NOT NULL,
  caption           VARCHAR(1000) NOT NULL DEFAULT '',
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_post_shares_source (source_post_id, created_at),
  KEY idx_post_shares_user_created (shared_by_user_id, created_at),
  CONSTRAINT fk_post_shares_source FOREIGN KEY (source_post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_post_shares_user FOREIGN KEY (shared_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  recipient_id BIGINT UNSIGNED NOT NULL,
  actor_id     BIGINT UNSIGNED NOT NULL,
  type         ENUM('post_like','post_comment','comment_reply') NOT NULL,
  post_id      BIGINT UNSIGNED NOT NULL,
  comment_id   BIGINT UNSIGNED NULL,
  dedupe_key   CHAR(64) NOT NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at      DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_notifications_dedupe (dedupe_key),
  KEY idx_notifications_recipient_created (recipient_id, created_at, id),
  KEY idx_notifications_recipient_unread (recipient_id, read_at, created_at),
  CONSTRAINT fk_notifications_recipient FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
