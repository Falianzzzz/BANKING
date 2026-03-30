CREATE TABLE `accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `owner_name` VARCHAR(255) NOT NULL,
  `currency` VARCHAR(255) NOT NULL,
  `balance` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
   `created_at` DATE NOT NULL,
   check ( balance > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `transactions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `account_id` INT(11) NOT NULL,
  `type` VARCHAR(255) NOT NULL,
  `amount` INT(11) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
    `created_at` DATE NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`id`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


