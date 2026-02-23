# Asia WordCamp 2026 – Group Expense Management

A simple Group Expense Management web application built with **Core PHP** and **MySQL** for managing shared trip expenses (e.g. Asia WordCamp 2026).

## Features

- **Add members** – Manage trip participants
- **Add categories** – Add expense categories from the frontend (Train, Dinner, Hotel, Taxi, etc.)
- **Add expenses** – Record who paid, category, total amount, description, date. Choose **All Members** or **selected members**; amount is divided equally among them and saved in `expense_shares`
- **Advance Payment** – Credit an amount to selected members (or all). Each selected member gets the full amount credited in settlement (Total Paid + Advance) − Total Share
- **Dashboard** – All expenses table (Paid By, Category, Total Amount, Description, Date) + **Member-wise Settlement** table (Total Paid, Total Share, Balance)
- **Delete expense** – Removes expense and its shares (CASCADE)
- **Responsive design** – Works on mobile and desktop
- **PDO** – Secure database access with prepared statements

## Folder Structure

```
asiacamp-php/
├── config.php          # Database connection (PDO)
├── index.php           # Dashboard: Add expense form, All expenses, Member-wise settlement
├── add_member.php      # Add new member form
├── add_category.php    # Add new category form
├── add_expense.php     # Process new expense + expense_shares (redirects to index)
├── summary.php         # Redirects to index#settlement
├── delete_expense.php  # Delete expense by ID (redirects to index)
├── schema.sql          # Database schema (members, categories, expenses, expense_shares)
├── includes/
│   ├── header.php      # HTML head + body start
│   └── footer.php      # Footer + body end
├── css/
│   └── style.css       # Styles (responsive, clean UI)
└── README.md           # This file
```

## Database Schema (summary)

- **members** – `id`, `name`, `created_at`
- **categories** – `id`, `name`, `created_at`
- **expenses** – `id`, `paid_by_member_id`, `category_id`, `total_amount`, `description`, `date`, `created_at`
- **expense_shares** – `id`, `expense_id`, `member_id`, `share_amount`
- **advance_payments** – `id`, `member_id`, `amount`, `date`, `description`, `created_at`

Full SQL is in `schema.sql`. If you already have the app, run the `CREATE TABLE advance_payments` part from `schema.sql` to add the new feature.

## Expense logic

- **All Members** – Total amount is divided equally among all members; one row in `expenses` + N rows in `expense_shares` (one per member).
- **Selected members** – Total amount is divided equally among selected members only; one row in `expenses` + N rows in `expense_shares` (one per selected member).

**Example 1:** Chirag paid 600 for Taxi for ALL members (6 members) → share_amount = 100 each → 6 rows in `expense_shares`.  
**Example 2:** Kaushik paid 3000 for train only for Chirag and Nikunj → share_amount = 1500 each → 2 rows in `expense_shares`.

## Member-wise Settlement

- **Total Paid** = Sum of `expenses.total_amount` where `paid_by_member_id` = member
- **Advance** = Sum of `advance_payments.amount` where `member_id` = member (credited to member)
- **Total Share** = Sum of `expense_shares.share_amount` where `member_id` = member
- **Balance** = (Total Paid + Advance) − Total Share  
  - **Balance > 0** → should **receive** money  
  - **Balance < 0** → should **pay** money  
  - **Balance = 0** → settled

## Requirements

- PHP 7.4+ (with PDO MySQL extension)
- MySQL 5.7+ or MariaDB
- Web server (Apache/Nginx) or PHP built-in server

## Step-by-Step Setup

1. **Create the database** – Import `schema.sql` (e.g. via phpMyAdmin or MySQL CLI). This creates `asiacamp_expenses` and tables: `members`, `categories`, `expenses`, `expense_shares`.
2. **Configure** – Edit `config.php` with your DB_USER and DB_PASS.
3. **Run** – Open `http://localhost/asiacamp-php/` (WAMP) or run `php -S localhost:8000` and open `http://localhost:8000/`.
4. **First use** – Add members (**Add Member**), add categories (**Add Category**), then add expenses on the **Dashboard** (Paid By, Category, Total Amount, Description, Date, and Expense For: All Members or selected members).

## Tech stack

- Core PHP (no framework)
- MySQL with PDO
- Plain HTML + CSS
- Responsive, minimal UI
