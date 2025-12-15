<div align="center">

# Crypto Trading Platform

A high-performance, real-time cryptocurrency trading platform built with modern web technologies.

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)](https://vuejs.org)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)

[Features](#-features) • [Installation](#-installation) • [Usage](#-usage) • [API](#-api-reference) • [Architecture](#-architecture)

</div>

---

## Overview

A full-stack trading platform demonstrating expertise in **financial data integrity**, **concurrency safety**, **real-time systems**, and **scalable architecture**. Built as a technical assessment showcasing modern Laravel and Vue.js development practices.

### Key Highlights

- **Race-Safe Transactions** - Row-level locking prevents double-spending
- **Atomic Order Matching** - All-or-nothing trade execution
- **Real-Time Updates** - WebSocket-powered instant notifications
- **Commission System** - Automated 1.5% fee calculation

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Vue 3 (Composition API), Pinia |
| **Styling** | Tailwind CSS 4 |
| **Database** | MySQL 8 |
| **Real-time** | Laravel Reverb (WebSockets) |
| **Auth** | Laravel Sanctum (Token-based) |

---

## Features

### Core Trading
- Limit order placement (Buy/Sell)
- Real-time order matching engine
- Live orderbook with price-time priority
- Order cancellation with fund release

### Wallet Management
- USD balance tracking
- Multi-asset support (BTC, ETH)
- Available vs locked amount display
- Real-time balance updates

### Real-Time Features
- Instant trade notifications via WebSocket
- Live orderbook updates
- Toast notifications for all actions
- Cross-browser synchronization

### Bonus Features
- Order filtering (symbol/side/status)
- Trade history with commission tracking
- Volume calculation preview
- Animated toast alerts

---

## Installation

### Prerequisites

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- MySQL 8.0+

### Quick Start

```bash
# Clone the repository
git clone <https://github.com/Israrminhas1/technical-assessment.git>
cd technical-assessment

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_DATABASE=trading_platform
DB_USERNAME=root
DB_PASSWORD=

# Run migrations and seed test data
php artisan migrate --seed

# Build frontend assets
npm run build
```

### Starting the Application

Open two terminal windows:

```bash
# Terminal 1: Laravel Server
php artisan serve

# Terminal 2: WebSocket Server
php artisan reverb:start
```

Access the application at `http://localhost:8000`

---

## Usage

### Test Accounts

| User | Email | Password | USD Balance | Assets |
|:-----|:------|:---------|:------------|:-------|
| Alice | `alice@example.com` | `password` | $100,000 | 10 ETH |
| Bob | `bob@example.com` | `password` | $50,000 | 2 BTC, 50 ETH |

### Testing Order Matching

1. **Open two browsers** (use incognito for the second)
2. **Login as Bob** in browser 1, **Alice** in browser 2
3. **Bob creates a sell order:**
   - Symbol: BTC
   - Side: Sell
   - Price: 95,000
   - Amount: 0.01
4. **Alice creates a matching buy order:**
   - Symbol: BTC
   - Side: Buy
   - Price: 95,000
   - Amount: 0.01
5. **Observe:** Both dashboards update instantly via WebSocket

### Commission Example

```
Trade: 0.01 BTC @ $95,000

Trade Value:     $950.00
Commission (1.5%): $14.25
─────────────────────────
Buyer Pays:      $964.25
Seller Receives: $950.00
```

---

## API Reference

### Authentication

| Method | Endpoint | Description |
|:-------|:---------|:------------|
| `POST` | `/api/register` | Create new account |
| `POST` | `/api/login` | Authenticate & get token |
| `POST` | `/api/logout` | Revoke current token |

### Trading

| Method | Endpoint | Description |
|:-------|:---------|:------------|
| `GET` | `/api/profile` | Get balance & assets |
| `GET` | `/api/orders?symbol=BTC` | Get orderbook |
| `GET` | `/api/orders/my` | Get user's orders |
| `POST` | `/api/orders` | Place new order |
| `POST` | `/api/orders/{id}/cancel` | Cancel open order |
| `GET` | `/api/trades/my` | Get trade history |

### Request Examples

**Place Order:**
```json
POST /api/orders
{
  "symbol": "BTC",
  "side": "buy",
  "price": "95000",
  "amount": "0.01"
}
```

**Response:**
```json
{
  "message": "Order matched successfully",
  "order": { ... },
  "matched": true,
  "match_details": {
    "price": "95000",
    "amount": "0.01",
    "total": "950",
    "commission": "14.25"
  }
}
```

---

## Architecture

### Database Schema

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│     users       │     │     assets      │     │     orders      │
├─────────────────┤     ├─────────────────┤     ├─────────────────┤
│ id              │────<│ user_id         │     │ id              │
│ name            │     │ symbol          │     │ user_id         │
│ email           │     │ amount          │     │ symbol          │
│ password        │     │ locked_amount   │     │ side            │
│ balance         │     │ timestamps      │     │ price           │
│ timestamps      │     └─────────────────┘     │ amount          │
└─────────────────┘                             │ status          │
        │                                       │ timestamps      │
        │         ┌─────────────────┐           └─────────────────┘
        │         │     trades      │                   │
        │         ├─────────────────┤                   │
        └────────>│ buyer_id        │<──────────────────┘
                  │ seller_id       │
                  │ buy_order_id    │
                  │ sell_order_id   │
                  │ symbol          │
                  │ price           │
                  │ amount          │
                  │ total           │
                  │ commission      │
                  │ timestamps      │
                  └─────────────────┘
```

### Order Matching Logic

```
┌──────────────────────────────────────────────────────────────┐
│                    ORDER MATCHING ENGINE                      │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  BUY ORDER                        SELL ORDER                 │
│  ─────────                        ──────────                 │
│  Match if:                        Match if:                  │
│  sell.price <= buy.price          buy.price >= sell.price    │
│  sell.amount == buy.amount        buy.amount == sell.amount  │
│                                                              │
│  Execution Price: Maker's price (existing order)             │
│  Commission: 1.5% from buyer                                 │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### Race Safety Implementation

```php
// All critical operations use row-level locking
DB::transaction(function () {
    $user = User::where('id', $id)->lockForUpdate()->first();
    $asset = Asset::where('user_id', $id)->lockForUpdate()->first();

    // Safe balance/asset manipulation
    $user->balance = bcsub($user->balance, $amount, 8);
    $user->save();
});
```

### Real-Time Event Flow

```
┌─────────┐     ┌─────────┐     ┌─────────┐     ┌─────────┐
│  User   │────>│   API   │────>│ Reverb  │────>│ Browser │
│ Action  │     │ Server  │     │ Server  │     │  Echo   │
└─────────┘     └─────────┘     └─────────┘     └─────────┘
                     │
                     ▼
              ┌─────────────┐
              │  Database   │
              │ Transaction │
              └─────────────┘
```

---

## Project Structure

```
app/
├── Events/
│   ├── OrderMatched.php          # Trade execution broadcast
│   └── OrderPlaced.php           # Orderbook update broadcast
├── Http/Controllers/Api/
│   ├── AuthController.php        # Authentication endpoints
│   ├── ProfileController.php     # Wallet & balance
│   └── OrderController.php       # Order & trade management
├── Models/
│   ├── User.php                  # User with balance
│   ├── Asset.php                 # Crypto holdings
│   ├── Order.php                 # Trade orders
│   └── Trade.php                 # Executed trades
└── Services/
    └── OrderService.php          # Core matching engine

resources/js/
├── router/index.js               # Vue Router config
├── stores/
│   ├── auth.js                   # Authentication state
│   └── trading.js                # Trading state
├── services/
│   ├── api.js                    # Axios HTTP client
│   └── echo.js                   # WebSocket client
└── views/
    ├── Login.vue                 # Login page
    ├── Register.vue              # Registration page
    └── Dashboard.vue             # Main trading interface
```

---

## Development

```bash
# Development server with hot reload
npm run dev

# Debug WebSocket connections
php artisan reverb:start --debug

# Fresh database reset
php artisan migrate:fresh --seed

# Clear all caches
php artisan optimize:clear
```

---

## Security Considerations

- **SQL Injection:** Prevented via Eloquent ORM
- **XSS:** Vue.js auto-escaping
- **CSRF:** Sanctum token authentication
- **Race Conditions:** Database-level locking
- **Input Validation:** Laravel form requests

---

## Performance Features

- **BCMath:** Precise decimal calculations (8 decimal places)
- **Database Indexing:** Optimized queries for orderbook
- **Lazy Loading:** Vue components loaded on demand
- **Connection Pooling:** Efficient database connections

---

<div align="center">

## License

This project was created for technical assessment purposes.

---

**Built with modern web technologies for scalability and reliability.**

</div>
