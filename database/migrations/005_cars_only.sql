-- Migration 005 - Cars-only catalog
-- Wipes test orders and rebuilds the catalog with car categories and products
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM order_items;
DELETE FROM payments;
DELETE FROM orders;
DELETE FROM cart_items;
DELETE FROM carts;
DELETE FROM products;
DELETE FROM categories;

ALTER TABLE products   AUTO_INCREMENT = 1;
ALTER TABLE categories AUTO_INCREMENT = 1;
ALTER TABLE orders     AUTO_INCREMENT = 1;
ALTER TABLE order_items AUTO_INCREMENT = 1;
ALTER TABLE payments   AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============ Categories ============
INSERT INTO categories (category_id, category_name, category_description) VALUES
(1, 'Sedans',      'Comfortable 4-door cars for everyday driving'),
(2, 'SUVs',        'Spacious sports utility vehicles for families and adventures'),
(3, 'Sports Cars', 'High-performance coupes and roadsters'),
(4, 'Pickups',     'Rugged utility trucks for work and play'),
(5, 'Hatchbacks',  'Compact, efficient cars perfect for the city'),
(6, 'Electric',    'Zero-emission electric and hybrid vehicles');

-- ============ Products ============
INSERT INTO products (name, description, price, stock, category_id, image_path) VALUES
-- Sedans
('Executive Sedan',       'Reliable executive sedan, automatic transmission, low mileage, full service history',  38000.00, 5, 1, 'sedan.jpg'),
('Compact City Sedan',    'Fuel-efficient 4-door sedan, perfect for daily commuting and family use',              22500.00, 8, 1, 'sedan.jpg'),
('Luxury Business Sedan', 'Leather interior, panoramic roof, advanced driver assistance, warranty included',      62000.00, 3, 1, 'sedan.jpg'),
('Sport Sedan',           'Twin-turbo performance sedan, sport-tuned suspension, premium sound system',           78000.00, 2, 1, 'martin-katler-94lAQc7ipNg-unsplash.jpg'),

-- SUVs
('Family SUV',            'Spacious 7-seat SUV, automatic, low fuel consumption, ideal for large families',       45000.00, 4, 2, 'suv.jpg'),
('Compact Crossover SUV', 'Versatile 5-seat crossover, excellent fuel economy, all-wheel drive',                 33500.00, 6, 2, 'suv.jpg'),
('Luxury SUV',            'Premium full-size SUV with leather interior, panoramic roof, and off-road capability',95000.00, 2, 2, 'suv.jpg'),
('Off-Road SUV',          'Rugged 4x4 SUV with locking differentials and raised suspension, built for adventure', 58000.00, 3, 2, 'jannis-lucas-v-QQ5EBf_bY-unsplash.jpg'),

-- Sports Cars
('Sports Coupe',          'High-performance 2-door coupe, 0-100 in under 5 seconds, carbon fiber accents',        62000.00, 3, 3, 'sports-car.jpg'),
('Roadster Convertible',  'Open-top 2-seat roadster, rear-wheel drive, crisp handling, timeless design',          88000.00, 2, 3, 'sports-car.jpg'),
('Track-Ready Coupe',     'Circuit-tuned coupe, roll cage, racing seats, semi-slick tires, ready for the track', 115000.00, 1, 3, 'rodan-can-6cqJPeTIuls-unsplash.jpg'),
('Grand Tourer',          'Powerful GT car, luxurious interior, effortless long-distance cruising',               135000.00, 2, 3, 'martin-katler-94lAQc7ipNg-unsplash.jpg'),

-- Pickups
('Double-Cab Pickup',     '4x4 double-cab pickup, 3.5-ton towing capacity, bed liner, tow bar',                   52000.00, 4, 4, 'suv.jpg'),
('Work Truck',            'Reliable single-cab pickup, ideal for contractors and small business owners',          34000.00, 6, 4, 'joey-banks-YApiWyp0lqo-unsplash.jpg'),
('Luxury Pickup',         'Fully-loaded double-cab, leather interior, premium audio, off-road package',           72000.00, 3, 4, 'suv.jpg'),

-- Hatchbacks
('City Hatchback',        'Compact 5-door hatchback, easy to park, great fuel economy, ideal for cities',         19500.00, 10, 5, 'jon-koop-khYVyHiNZo0-unsplash.jpg'),
('Sport Hatch',           'Hot hatch with turbocharged engine and sport suspension, fun to drive daily',          36500.00, 5, 5, 'jon-koop-khYVyHiNZo0-unsplash.jpg'),
('Economy Hatchback',     'Budget-friendly 3-door hatchback, low running costs, perfect first car',              14500.00, 12, 5, 'jon-koop-khYVyHiNZo0-unsplash.jpg'),

-- Electric
('Electric Sedan',        'All-electric 4-door sedan, 500 km range, fast-charging, zero emissions',               68000.00, 3, 6, 'sedan.jpg'),
('Hybrid SUV',            'Plug-in hybrid SUV, 60 km electric range, seamless switching to petrol power',         54000.00, 4, 6, 'suv.jpg'),
('Electric Hatchback',    'Compact electric hatchback, 350 km range, perfect for urban commuters',                32000.00, 5, 6, 'jon-koop-khYVyHiNZo0-unsplash.jpg');

INSERT IGNORE INTO migrations (id) VALUES ('005_cars_only');