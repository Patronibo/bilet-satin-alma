PRAGMA foreign_keys = ON;

-- ============================
-- TABLO OLUŞTURMALARI
-- ============================

DROP TABLE IF EXISTS User_Coupons;
DROP TABLE IF EXISTS Coupons;
DROP TABLE IF EXISTS Booked_Seats;
DROP TABLE IF EXISTS Tickets;
DROP TABLE IF EXISTS Trips;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS Bus_Company;

CREATE TABLE Bus_Company (
  id TEXT PRIMARY KEY,
  name TEXT UNIQUE NOT NULL,
  logo_path TEXT,
  created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE User (
  id TEXT PRIMARY KEY,
  full_name TEXT NOT NULL,
  email TEXT NOT NULL,
  role TEXT NOT NULL CHECK(role IN ('user','company','admin')),
  password TEXT NOT NULL,
  company_id TEXT,
  balance INTEGER DEFAULT 800,
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY(company_id) REFERENCES Bus_Company(id) ON DELETE SET NULL
);

CREATE TABLE Trips (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  company_id TEXT NOT NULL,
  departure_city TEXT NOT NULL,
  destination_city TEXT NOT NULL,
  departure_time TEXT NOT NULL,
  arrival_time TEXT NOT NULL,
  price INTEGER NOT NULL,
  capacity INTEGER NOT NULL,
  bus_type TEXT DEFAULT '2+2',
  bus_plate TEXT,
  bus_model TEXT,
  description TEXT,
  created_date TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY(company_id) REFERENCES Bus_Company(id) ON DELETE CASCADE
);

CREATE TABLE Tickets (
  id TEXT PRIMARY KEY,
  trip_id INTEGER NOT NULL,
  user_id TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','canceled','expired')),
  total_price INTEGER NOT NULL,
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY(trip_id) REFERENCES Trips(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES User(id) ON DELETE CASCADE
);

CREATE TABLE Booked_Seats (
  id TEXT PRIMARY KEY,
  ticket_id TEXT NOT NULL,
  seat_number INTEGER NOT NULL,
  gender TEXT CHECK(gender IN ('male','female')) DEFAULT 'male',
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY(ticket_id) REFERENCES Tickets(id) ON DELETE CASCADE
);

CREATE TABLE Coupons (
  id TEXT PRIMARY KEY,
  code TEXT UNIQUE NOT NULL,
  discount REAL NOT NULL,
  usage_limit INTEGER DEFAULT 1,
  expire_date TEXT,
  created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE User_Coupons (
  id TEXT PRIMARY KEY,
  coupon_id TEXT NOT NULL,
  user_id TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY(coupon_id) REFERENCES Coupons(id) ON DELETE CASCADE,
  FOREIGN KEY(user_id) REFERENCES User(id) ON DELETE CASCADE
);

CREATE TABLE Admin_Access_Tokens (
  id TEXT PRIMARY KEY,
  token TEXT UNIQUE NOT NULL,
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  expires_at TEXT NOT NULL
);

-- ============================
-- ADMIN KULLANICI EKLEME
-- ============================

INSERT INTO User (
  id, full_name, email, password, role, created_at
) VALUES (
  'admin-uuid-001',
  'Sistem Admin',
  'admin@bilet.com',
  '$2y$10$fZvX/ix4449P/T60mYxx/ehcr3JUtT5h10A1DNqBS8ClqeGg65ovS',
  'admin',
  datetime('now', 'localtime')
);
