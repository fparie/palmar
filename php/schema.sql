-- Palmar schema (MySQL / MariaDB)
-- Run once: mysql -u <user> -p palmar < schema.sql

CREATE TABLE IF NOT EXISTS placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    address VARCHAR(500) NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_phone VARCHAR(100) NOT NULL,
    contact_email VARCHAR(255) NOT NULL,
    pedestal TINYINT(1) NOT NULL DEFAULT 0,
    agreement_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS placement_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_placement_photos_placement
        FOREIGN KEY (placement_id) REFERENCES placements(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_placement_photos_placement ON placement_photos (placement_id);
