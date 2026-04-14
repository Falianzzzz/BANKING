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
;
  
INSERT INTO `accounts` (`owner_name`, `currency`, `balance`, `created_at`) VALUES
('Mario Rossi', 'EUR', 1500, '2023-01-15'),
('Luigi Bianchi', 'EUR', 2800, '2023-02-10'),
('Elena Verdi', 'USD', 5000, '2023-03-05'),
('Sofia Neri', 'EUR', 1200, '2023-04-20'),
('Marco Bruno', 'GBP', 3500, '2023-05-12'),
('Giulia Conti', 'EUR', 900, '2023-06-01'),
('Alessandro Riva', 'USD', 2200, '2023-07-14'),
('Francesca Sala', 'EUR', 4100, '2023-08-22'),
('Roberto Galli', 'CHF', 6000, '2023-09-30'),
('Anna Longo', 'EUR', 300, '2023-10-05');


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
