CREATE DATABASE wifi_registration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wifi_registration;

-- Table for storing user registration
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    user_type ENUM('บุคลากรภายใน','นักศึกษา','บุคคลภายนอก') NOT NULL,
    staff_id VARCHAR(50) DEFAULT NULL,
    student_id VARCHAR(50) DEFAULT NULL,
    citizen_id VARCHAR(20) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    email_facebook VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table for device information
CREATE TABLE devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_type ENUM('Notebook','Smartphone','Tablet','อื่น ๆ') NOT NULL,
    device_brand_model VARCHAR(100),
    os ENUM('Windows','macOS','iOS','Android','อื่น ๆ') NOT NULL,
    asset_number VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table for usage request details
CREATE TABLE requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    access_type ENUM('Guest Wi-Fi','เครือข่ายภายในมหาวิทยาลัยฯ') NOT NULL,
    purpose TEXT,
    university_branch VARCHAR(100),
    status ENUM('รอดำเนินการ','อนุมัติ','ปฏิเสธ') DEFAULT 'รอดำเนินการ',
    approved_by VARCHAR(100),
    approved_position VARCHAR(100),
    approved_date DATE DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Table for agreement acceptance
CREATE TABLE agreements (
    agreement_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    accept_terms BOOLEAN DEFAULT FALSE,
    signed_name VARCHAR(150),
    signed_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

