-- Asia WordCamp 2026 - Group Expense Management
-- Database Schema for MySQL

CREATE DATABASE IF NOT EXISTS asiacamp_expenses;
USE asiacamp_expenses;

-- Members table
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table (admin adds from frontend)
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paid_by_member_id INT NOT NULL,
    category_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paid_by_member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    INDEX idx_date (date),
    INDEX idx_paid_by (paid_by_member_id),
    INDEX idx_category_id (category_id)
);

-- Expense shares: who owes how much for each expense
CREATE TABLE IF NOT EXISTS expense_shares (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    member_id INT NOT NULL,
    share_amount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_expense_id (expense_id),
    INDEX idx_member_id (member_id)
);

-- Advance payments: credited to member in settlement (Total Paid = expenses + advance)
CREATE TABLE IF NOT EXISTS advance_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL DEFAULT CURRENT_DATE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_member_id (member_id),
    INDEX idx_date (date)
);

-- Optional: Insert sample categories for testing
-- INSERT INTO categories (name) VALUES ('Train'), ('Dinner'), ('Hotel'), ('Taxi'), ('Other');
