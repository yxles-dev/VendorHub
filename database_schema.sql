-- Database Schema for Barangay Market Registration System

-- Main Vendors Table
CREATE TABLE vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    fullname VARCHAR(255) NOT NULL,
    sex ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    emergency_contact VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    business_name VARCHAR(255) NOT NULL,
    category ENUM('Isda', 'Karne', 'Prutas', 'Gulay') NOT NULL,
    registration_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Health Declaration Responses Table
CREATE TABLE health_declarations (
    health_declaration_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL UNIQUE,
    hand_washing_station BOOLEAN NOT NULL,
    color_coded_trash_bin BOOLEAN NOT NULL,
    wear_ppe BOOLEAN NOT NULL,
    clean_stall_before_after BOOLEAN NOT NULL,
    no_smoking_agreement BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE
);

-- Agreements/Certifications Table
CREATE TABLE agreements (
    agreement_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL UNIQUE,
    health_certificates_valid BOOLEAN NOT NULL,
    cleaning_methods_proper BOOLEAN NOT NULL,
    waste_disposal_proper BOOLEAN NOT NULL,
    understands_noncompliance BOOLEAN NOT NULL,
    products_fresh_and_legal BOOLEAN NOT NULL,
    fit_to_work_no_disease BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE
);

-- Uploaded Documents Table
CREATE TABLE documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    document_type ENUM(
        'barangay_clearance',
        'dti_registration',
        'government_id',
        'health_certificate',
        'sanitary_permit',
        'proof_of_stall'
    ) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id) ON DELETE CASCADE,
    UNIQUE KEY unique_vendor_doctype (vendor_id, document_type)
);

-- Indexes for better query performance
CREATE INDEX idx_vendor_status ON vendors(registration_status);
CREATE INDEX idx_vendor_category ON vendors(category);
CREATE INDEX idx_vendor_created ON vendors(created_at);
CREATE INDEX idx_documents_vendor ON documents(vendor_id);
