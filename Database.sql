-- Create database
CREATE DATABASE citywing_shuttles;
USE citywing_shuttles;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    description TEXT,
    full_description TEXT,
    features JSON,
    badge VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cars table
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    description TEXT,
    specs JSON,
    features JSON,
    badge VARCHAR(50),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    service_type VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    country_code VARCHAR(10),
    passengers INT NOT NULL,
    arrival_date DATE NOT NULL,
    time TIME NOT NULL,
    pickup_address TEXT NOT NULL,
    dropoff_address TEXT NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Contact messages table
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    newsletter BOOLEAN DEFAULT FALSE,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Cart items table (for logged-in users)
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_type ENUM('service', 'car') NOT NULL,
    item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Testimonials table
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_name VARCHAR(100) NOT NULL,
    author_image VARCHAR(255),
    author_location VARCHAR(100),
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO services (name, category, price, image_url, description, full_description, features, badge) VALUES
('Airport Transfers', 'transfer', 45.00, 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957', 'Reliable airport transfers to and from all major airports', 'Comprehensive airport transfer service with flight monitoring', '["Meet & Greet", "Flight Monitoring", "Luggage Assistance"]', 'Popular'),
('Windhoek City Tour', 'tour', 75.00, 'https://images.unsplash.com/photo-1572869518016-9c3255c41279', 'Explore Namibia capital city', 'Guided tour of Windhoek landmarks', '["Professional Guide", "Historical Sites", "Local Markets"]', 'Featured');

INSERT INTO cars (name, type, price, image_url, description, specs, features, badge) VALUES
('Toyota Corolla', 'sedan', 65.00, 'https://images.unsplash.com/photo-1603584173870-7f23fdae1b7a', 'Reliable and fuel-efficient sedan', '{"passengers": 5, "luggage": 2, "transmission": "Automatic", "fuel": "Petrol"}', '["Air Conditioning", "Bluetooth", "GPS"]', 'Popular'),
('Toyota RAV4', 'suv', 85.00, 'https://images.unsplash.com/photo-1566479137142-0c17d0a82d4c', 'Comfortable SUV with ample space', '{"passengers": 5, "luggage": 3, "transmission": "Automatic", "fuel": "Petrol"}', '["Air Conditioning", "Touchscreen", "Rear Camera"]', 'Family Choice');

INSERT INTO testimonials (author_name, author_image, author_location, content, rating) VALUES
('Sarah Johnson', 'https://randomuser.me/api/portraits/women/44.jpg', 'Tourist from USA', 'CityWing Shuttles provided exceptional service during our trip to Namibia. The vehicles were comfortable and drivers were knowledgeable.', 5),
('Michael Brown', 'https://randomuser.me/api/portraits/men/32.jpg', 'Business Traveler', 'I have used CityWing for both airport transfers and day tours. Their professionalism and attention to detail made our trip memorable.', 5);