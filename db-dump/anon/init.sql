CREATE DATABASE IF NOT EXISTS hr;

USE hr;

CREATE TABLE people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    age INT,
    email VARCHAR(255)
);

INSERT INTO people (name, age, email) VALUES ('John Doe', 28, 'johndoe@example.com');
INSERT INTO people (name, age, email) VALUES ('Jane Smith', 32, 'janesmith@example.com');
INSERT INTO people (name, age, email) VALUES ('Michael Brown', 45, 'michaelbrown@example.com');
INSERT INTO people (name, age, email) VALUES ('Emily White', 22, 'emilywhite@example.com');
INSERT INTO people (name, age, email) VALUES ('Daniel Jones', 30, 'danieljones@example.com');
INSERT INTO people (name, age, email) VALUES ('Jessica Taylor', 25, 'jessicataylor@example.com');
INSERT INTO people (name, age, email) VALUES ('William Davis', 40, 'williamdavis@example.com');
INSERT INTO people (name, age, email) VALUES ('Olivia Miller', 19, 'oliviamiller@example.com');
INSERT INTO people (name, age, email) VALUES ('Henry Wilson', 34, 'henrywilson@example.com');
INSERT INTO people (name, age, email) VALUES ('Sophia Moore', 27, 'sophiamoore@example.com');
