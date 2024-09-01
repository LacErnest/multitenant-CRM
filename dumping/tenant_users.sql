-- Create the users
/* CREATE USER 'bffaf7eeecb4476c8a9b6d0032b66228'@'%' IDENTIFIED BY 'd32162657f4c631cee263ff66277454a';
CREATE USER 'a822cdf9723d48aabb6df1c5000e8598'@'%' IDENTIFIED BY 'bed5ea3456c052a68912cf46549a59a2';
CREATE USER 'a0286d06ade841cca85228adbed31b51'@'%' IDENTIFIED BY '6d81d5814791b5819c3839771d36b76e';
CREATE USER '9af881e42f94461c84e8fd8cb00c124d'@'%' IDENTIFIED BY 'd475d6bce5c9b5d7ad6f562e4aa479bd';
CREATE USER '8f24bf7a0fab465caf9e8ca0d3982fd5'@'%' IDENTIFIED BY 'b68e91f80dfdf03dcf1c48ee2667f713';
CREATE USER '84e743fc410b4efa946a9ddfc9f4d646'@'%' IDENTIFIED BY '0b72e276481ef522324e696d28273ad8';
CREATE USER '7eb615e7cfa240d19ffdc40303c111ef'@'%' IDENTIFIED BY 'b08f20f5cb45f0475dd7b6df51dd8df5';
CREATE USER '3e7c6ecf5cfe4637926b25336c5ef833'@'%' IDENTIFIED BY '26b49b493363fad12366c0fa968e3bfe';


-- Grant additional privileges
GRANT ALL ON *.* TO 'bffaf7eeecb4476c8a9b6d0032b66228'@'%';
GRANT ALL ON *.* TO 'a822cdf9723d48aabb6df1c5000e8598'@'%';
GRANT ALL ON *.* TO 'a0286d06ade841cca85228adbed31b51'@'%';
GRANT ALL ON *.* TO '9af881e42f94461c84e8fd8cb00c124d'@'%';
GRANT ALL ON *.* TO '8f24bf7a0fab465caf9e8ca0d3982fd5'@'%';
GRANT ALL ON *.* TO '84e743fc410b4efa946a9ddfc9f4d646'@'%';
GRANT ALL ON *.* TO '7eb615e7cfa240d19ffdc40303c111ef'@'%';
GRANT ALL ON *.* TO '3e7c6ecf5cfe4637926b25336c5ef833'@'%';

-- Ensure the privileges are not grantable
REVOKE GRANT OPTION ON *.* FROM 'bffaf7eeecb4476c8a9b6d0032b66228'@'%';
REVOKE GRANT OPTION ON *.* FROM 'a822cdf9723d48aabb6df1c5000e8598'@'%';
REVOKE GRANT OPTION ON *.* FROM 'a0286d06ade841cca85228adbed31b51'@'%';
REVOKE GRANT OPTION ON *.* FROM '9af881e42f94461c84e8fd8cb00c124d'@'%';
REVOKE GRANT OPTION ON *.* FROM '8f24bf7a0fab465caf9e8ca0d3982fd5'@'%';
REVOKE GRANT OPTION ON *.* FROM '84e743fc410b4efa946a9ddfc9f4d646'@'%';
REVOKE GRANT OPTION ON *.* FROM '7eb615e7cfa240d19ffdc40303c111ef'@'%';
REVOKE GRANT OPTION ON *.* FROM '3e7c6ecf5cfe4637926b25336c5ef833'@'%'; */

INSERT INTO `users`(`id`, `first_name`, `last_name`, `email`, `primary_account`, `role`, `super_user`, `google2fa`, `created_at`, `updated_at`, `password`) VALUES
(UUID(), 'Douglas', 'Wafo', 'douglas.wafo@magicmedia.studio', 1, 0, 0, 0, '2022-01-28 17:05:09', '2022-01-28 17:05:09', '$2y$12$kj5EJur5wI6pDZbZAgt6AedfIUlLfAOi8k6mpIWk5Zr14XMvBduuS'),
(UUID(), 'Marcia', 'Cruz', 'marcia.cruz@magicmedia.studio', 1, 0, 0, 0, '2022-01-28 17:05:09', '2022-01-28 17:05:09', '$2y$12$kj5EJur5wI6pDZbZAgt6AedfIUlLfAOi8k6mpIWk5Zr14XMvBduuS'),
(UUID(), 'David', 'Rosario', 'david.rosario@magicmedia.studio', 1, 0, 0, 0, '2022-01-28 17:05:09', '2022-01-28 17:05:09', '$2y$12$kj5EJur5wI6pDZbZAgt6AedfIUlLfAOi8k6mpIWk5Zr14XMvBduuS'),
(UUID(), 'Ernest', 'Tsamo', 'ernest.tsamo@magicmedia.studio', 1, 0, 0, 0, '2022-01-28 17:05:09', '2022-01-28 17:05:09', '$2y$12$kj5EJur5wI6pDZbZAgt6AedfIUlLfAOi8k6mpIWk5Zr14XMvBduuS');
