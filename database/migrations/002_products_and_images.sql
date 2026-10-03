-- Migration 002 - Real product images + expanded catalog
-- Safe to re-run

UPDATE categories SET category_name = 'Wardrobes', category_description = 'Bedroom wardrobes and closets'    WHERE category_id = 1;
UPDATE categories SET category_name = 'Chairs',    category_description = 'Chairs and seating'               WHERE category_id = 2;
UPDATE categories SET category_name = 'Sofas',     category_description = 'Couches and living room furniture' WHERE category_id = 3;
UPDATE categories SET category_name = 'Kitchen',   category_description = 'Kitchen cabinets and storage'      WHERE category_id = 4;
UPDATE categories SET category_name = 'Vehicles',  category_description = 'Cars and SUVs'                    WHERE category_id = 5;

UPDATE products SET name='Sleek Wardrobe', description='Lesotho-made 3-door wardrobe with full-length mirror', price=2700.00, category_id=1, image_path='wardrobe1.jpg' WHERE product_id=3;
UPDATE products SET name='Classic Wooden Chair', description='Solid wood chair with padded seat, ideal for dining', price=850.00, category_id=2, image_path='chair1.jpg' WHERE product_id=4;
UPDATE products SET name='Modern Dining Chair', description='Upholstered dining chair with tapered legs', price=950.00, category_id=2, image_path='chair2.jpg' WHERE product_id=5;
UPDATE products SET name='Executive Sedan', description='Reliable executive sedan, automatic, low mileage', price=38000.00, category_id=5, image_path='sedan.jpg' WHERE product_id=6;

INSERT INTO products (name, description, price, stock, category_id, image_path) VALUES
('Elegant 4-Door Wardrobe',     'Spacious wardrobe with wood-grain finish and mirror',   3200.00,  6, 1, 'wardrobe2.jpg'),
('Sliding-Door Wardrobe',       'Modern sliding doors with soft-close rails',           4100.00,  4, 1, 'wardrobe3.jpg'),
('Compact Wardrobe',            'Space-saving 2-door wardrobe for small rooms',         1850.00,  8, 1, 'wardrobe4.jpg'),
('Designer Accent Armchair',    'Premium designer armchair, mid-century style',         2400.00,  6, 2, 'martin-katler-94lAQc7ipNg-unsplash.jpg'),
('Bohemian Living Chair',       'Warm boho-style chair with woven accents',             1900.00,  7, 2, 'joey-banks-YApiWyp0lqo-unsplash.jpg'),
('Reading Corner Chair',        'Cozy armchair with padded headrest',                   2100.00,  8, 2, 'rodan-can-6cqJPeTIuls-unsplash.jpg'),
('Luxury Sofa Combo',           '3-seater sofa with matching ottoman and cushions',     8900.00,  3, 3, 'combo1.jpg'),
('Classic 3-Seater Sofa',       'Premium fabric upholstery, hardwood frame',            6500.00,  5, 3, 'couch1.jpg'),
('Recliner Couch',              'Single-seat recliner with adjustable back',            4200.00,  6, 3, 'couch2.jpg'),
('Scandinavian Minimalist Sofa','Clean lines, light fabric, oak legs',                  4900.00,  5, 3, 'jon-koop-khYVyHiNZo0-unsplash.jpg'),
('Outdoor Patio Sofa',          'Weather-resistant outdoor lounge sofa',                5200.00,  4, 3, 'jannis-lucas-v-QQ5EBf_bY-unsplash.jpg'),
('Modern Kitchen Cabinet',      'Contemporary kitchen cabinet with granite-style top',  5500.00,  3, 4, 'kicthen1.jpg'),
('Loft-Style Kitchen Set',      'Industrial loft kitchen storage unit',                 7800.00,  2, 4, 'kicthen1.jpg'),
('Family SUV',                  'Spacious 7-seat SUV, automatic, low consumption',      45000.00, 2, 5, 'suv.jpg'),
('Sports Coupe',                'High-performance 2-door coupe',                        62000.00, 1, 5, 'sports-car.jpg');