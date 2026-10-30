-- users
CREATE TABLE Users (
   user_id INT PRIMARY KEY AUTO_INCREMENT,
   username VARCHAR(50) NOT NULL UNIQUE,
   first_name VARCHAR(50) NOT NULL,
   last_name VARCHAR(50) NOT NULL,
   email VARCHAR(100) UNIQUE NOT NULL,
   address VARCHAR(255),
   age TINYINT UNSIGNED,
   password_hash VARCHAR(255) NOT NULL,
   role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- admin user default
INSERT INTO Users
   (username, first_name, last_name, email, password_hash, role)
VALUES
   ('admin', 'Airport', 'Administrator', 'admin@localhost',
   '$2y$10$a97vAVhkSGrIkxpHw1/uUuydmjapjKvwqi5YQXM3hUZuO.AorIG2q', 'admin');

-- registration
CREATE TABLE Passengers (
   passenger_id INT PRIMARY KEY AUTO_INCREMENT,
   first_name VARCHAR(50) NOT NULL,
   last_name VARCHAR(50) NOT NULL,
   email VARCHAR(100) UNIQUE NOT NULL,
   phone VARCHAR(20),
   added_by INT NOT NULL,
   updated_by INT NOT NULL,
   last_updated TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
   FOREIGN KEY (added_by) REFERENCES Users(user_id) ON DELETE RESTRICT,
   FOREIGN KEY (updated_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);

-- bookings
CREATE TABLE Bookings (
   booking_id INT PRIMARY KEY AUTO_INCREMENT,
   passenger_id INT NOT NULL,
   flight_number VARCHAR(10) NOT NULL,
   route_origin VARCHAR(100) NOT NULL,
   route_destination VARCHAR(100) NOT NULL,
   assigned_seat VARCHAR(5) NOT NULL,
   ticket_price DECIMAL(10, 2) NOT NULL,
   scheduled_departure DATETIME NULL,
   booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   customer_user_id INT NULL,
   added_by INT NOT NULL,
   updated_by INT NOT NULL,
   last_updated TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
   FOREIGN KEY (passenger_id) REFERENCES Passengers(passenger_id) ON DELETE RESTRICT,
   FOREIGN KEY (customer_user_id) REFERENCES Users(user_id) ON DELETE RESTRICT,
   FOREIGN KEY (added_by) REFERENCES Users(user_id) ON DELETE RESTRICT,
   FOREIGN KEY (updated_by) REFERENCES Users(user_id) ON DELETE RESTRICT
);
-- reschedule request
CREATE TABLE RescheduleRequests (
   request_id INT PRIMARY KEY AUTO_INCREMENT,
   booking_id INT NOT NULL,
   customer_user_id INT NOT NULL,
   requested_departure DATETIME NOT NULL,
   reason VARCHAR(1000) NOT NULL,
   status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
   admin_response VARCHAR(1000),
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   reviewed_by INT NULL,
   reviewed_at TIMESTAMP NULL,
   FOREIGN KEY (booking_id) REFERENCES Bookings(booking_id) ON DELETE CASCADE,
   FOREIGN KEY (customer_user_id) REFERENCES Users(user_id) ON DELETE RESTRICT,
   FOREIGN KEY (reviewed_by) REFERENCES Users(user_id) ON DELETE RESTRICT,
   INDEX idx_reschedule_status_created (status, created_at),
   INDEX idx_reschedule_customer (customer_user_id)
);
-- customer booking requests
CREATE TABLE BookingRequests (
   request_id INT PRIMARY KEY AUTO_INCREMENT,
   customer_user_id INT NOT NULL,
   passenger_first_name VARCHAR(50) NOT NULL,
   passenger_last_name VARCHAR(50) NOT NULL,
   passenger_email VARCHAR(100) NOT NULL,
   passenger_phone VARCHAR(20) NOT NULL,
   flight_number VARCHAR(10) NOT NULL,
   route_origin VARCHAR(100) NOT NULL,
   route_destination VARCHAR(100) NOT NULL,
   requested_departure DATETIME NOT NULL,
   status ENUM('pending', 'needs_changes', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
   admin_response VARCHAR(1000),
   created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   reviewed_by INT NULL,
   reviewed_at TIMESTAMP NULL,
   FOREIGN KEY (customer_user_id) REFERENCES Users(user_id) ON DELETE RESTRICT,
   FOREIGN KEY (reviewed_by) REFERENCES Users(user_id) ON DELETE RESTRICT,
   INDEX idx_booking_request_status_created (status, created_at),
   INDEX idx_booking_request_customer (customer_user_id)
);
-- auditlog
CREATE TABLE AuditLog (
   audit_id INT PRIMARY KEY AUTO_INCREMENT,
   entity_type VARCHAR(32) NOT NULL,
   entity_id INT NOT NULL,
   action_type ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
   actor_user_id INT NOT NULL,
   occurred_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (actor_user_id) REFERENCES Users(user_id) ON DELETE RESTRICT,
   INDEX idx_audit_entity (entity_type, entity_id),
   INDEX idx_audit_actor (actor_user_id)
);
