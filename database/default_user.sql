-- Attendra Default Users SQL Dump
-- Database: attendra
-- Table: users

INSERT INTO `users` (`id`, `name`, `email`, `staff_id`, `role`, `faculty`, `status`, `password`, `created_at`, `updated_at`)
VALUES
(101, 'Attendra Admin', 'admin@gmail.com', 'ADM001', 'admin', 'Canselori', 'active', '$2y$12$IFjNZIN90WXMjXpbm8617e2n1.vXvH/rdR2NkWi63Gj941GXP8W9m', NOW(), NOW()),
(102, 'Staff Organizer', 'staff@gmail.com', 'STF001', 'staff', 'FTMK', 'active', '$2y$12$IFjNZIN90WXMjXpbm8617e2n1.vXvH/rdR2NkWi63Gj941GXP8W9m', NOW(), NOW())
ON DUPLICATE KEY UPDATE
`name` = VALUES(`name`),
`role` = VALUES(`role`),
`password` = VALUES(`password`),
`status` = 'active',
`updated_at` = NOW();
