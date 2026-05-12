CREATE TABLE `accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
    `surname` VARCHAR(255) NOT NULL,
  `currency` VARCHAR(255) NOT NULL,
  `passwordhash`VARCHAR(255) NOT NULL,
   `salt` VARCHAR(100) NOT NULL,
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
;

INSERT INTO `accounts` (`name`,`surname` ,`currency`, `balance`, `created_at`, `passwordhash`, `salt`) VALUES
('Mario', 'Rossi', 'EUR', 1500, '2023-01-15', SHA2(CONCAT('pass123', 'salt_mario'), 256), 'salt_mario'),
('Luigi', 'Bianchi', 'EUR', 2800, '2023-02-10', SHA2(CONCAT('abc456', 'salt_luigi'), 256), 'salt_luigi'),
('Elena', 'Verdi', 'USD', 5000, '2023-03-05', SHA2(CONCAT('verdi789', 'salt_elena'), 256), 'salt_elena'),
('Sofia' ,'Neri', 'EUR', 1200, '2023-04-20', SHA2(CONCAT('sofia123', 'salt_sofia'), 256), 'salt_sofia'),
('Marco','Bruno', 'GBP', 3500, '2023-05-12', SHA2(CONCAT('bruno99', 'salt_marco'), 256), 'salt_marco'),
('Giulia','Conti', 'EUR', 900, '2023-06-01', SHA2(CONCAT('conti00', 'salt_giulia'), 256), 'salt_giulia'),
('Alessandro','Riva', 'USD', 2200, '2023-07-14', SHA2(CONCAT('riva88', 'salt_ale'), 256), 'salt_ale'),
('Francesca','Sala', 'EUR', 4100, '2023-08-22', SHA2(CONCAT('sala77', 'salt_fra'), 256), 'salt_fra'),
('Roberto','Galli', 'CHF', 6000, '2023-09-30', SHA2(CONCAT('galli66', 'salt_rob'), 256), 'salt_rob'),
('Anna', 'Longo', 'EUR', 300, '2023-10-05', SHA2(CONCAT('longo55', 'salt_anna'), 256), 'salt_anna');



INSERT INTO `transactions` (`account_id`, `type`, `amount`, `description`, `created_at`) VALUES
(1, 'deposit', 500, 'Stipendio Gennaio', '2023-01-20'),
(1, 'withdrawal', 50, 'Spesa supermercato', '2023-01-21'),
(2, 'deposit', 1000, 'Bonifico entrata', '2023-02-15'),
(3, 'deposit', 2500, 'Vendita azioni', '2023-03-10'),
(4, 'withdrawal', 100, 'Ricarica telefonica', '2023-04-25'),
(5, 'deposit', 1500, 'Rimborso spese', '2023-05-20'),
(6, 'withdrawal', 200, 'Prelievo ATM', '2023-06-05'),
(7, 'deposit', 800, 'Premio produzione', '2023-07-20'),
(8, 'withdrawal', 45, 'Abbonamento Netflix', '2023-08-25'),
(9, 'deposit', 1200, 'Affitto percepito', '2023-10-01');