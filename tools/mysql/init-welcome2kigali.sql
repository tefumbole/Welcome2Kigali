-- First-boot only (Docker). Creates the Node API schema next to Laravel.
CREATE DATABASE IF NOT EXISTS welcome2kigali_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON welcome2kigali.* TO 'w2k'@'%';
GRANT ALL PRIVILEGES ON welcome2kigali_api.* TO 'w2k'@'%';
FLUSH PRIVILEGES;
