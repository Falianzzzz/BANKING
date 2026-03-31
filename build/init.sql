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

INSERT INTO accounts (owner_name,currency,balance,created_at) VALUES("magnolfi","euro","10","2026-10-10");
INSERT INTO transactions (account_id,type,amount,description,created_at) VALUES (1,"depos",100," huhh","2026-03-10");
