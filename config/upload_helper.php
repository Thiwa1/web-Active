<?php
/**
 * Image Upload Helper
 * Handles file uploads securely and returns the file path.
 */

function uploadImage($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 10485760) {
    if (empty($file['name'])) {
        return null;
    }

    $fileName = basename($file['name']);
    $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate Type
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed: " . implode(', ', $allowedTypes));
    }

    // Validate Size (10MB default)
    if ($file['size'] > $maxSize) {
        throw new Exception("File too large. Max size: " . ($maxSize/1024/1024) . "MB");
    }

    // Generate Unique Name
    $uniqueName = uniqid() . '_' . time() . '.' . $fileType;
    $targetFilePath = $targetDir . $uniqueName;

    // Create Dir if not exists
    if (!file_exists($targetFilePath)) {
        // We assume dirs are created by migration script or manual setup
        // But for safety:
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
    }

    // Upload
    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return $targetFilePath; // Store this in DB
    } else {
        throw new Exception("Failed to upload file.");
    }
}
