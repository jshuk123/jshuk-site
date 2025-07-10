<?php
require_once __DIR__ . '/upload_helper.php';

class ImageManager {
    private $pdo;
    private $user_id;
    private $upload_base_dir;

    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->upload_base_dir = dirname(__DIR__) . '/uploads/businesses/';
        
        // Ensure base upload directory exists
        if (!is_dir($this->upload_base_dir)) {
            if (!mkdir($this->upload_base_dir, 0755, true)) {
                error_log("Failed to create base upload directory: " . $this->upload_base_dir);
                throw new Exception('Failed to create upload directory');
            }
        }
    }

    /**
     * Upload a new image using the standard upload_helper.php
     */
    public function uploadImage($file, $business_id, $type = 'gallery') {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Validate file
            if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                error_log("Invalid file upload: " . print_r($file, true));
                throw new Exception('Invalid file upload');
            }

            // Create business directory (same as post_business.php)
            $business_upload_dir = $this->upload_base_dir . $business_id;
            if (!is_dir($business_upload_dir)) {
                if (!mkdir($business_upload_dir, 0777, true)) {
                    throw new Exception("Failed to create business directory: $business_upload_dir");
                }
            }

            // If uploading a main image, update existing main image to gallery
            if ($type === 'main') {
                $stmt = $this->pdo->prepare("UPDATE business_images SET sort_order = 1 WHERE business_id = ? AND sort_order = 0");
                $stmt->execute([$business_id]);
            }

            // Use upload_helper.php to handle the actual file upload
            $prefix = ($type === 'main') ? 'main' : 'gallery';
            $file_path = handle_image_upload($file, $business_upload_dir, $prefix);

            // Determine sort_order
            $sort_order = 0; // Default for main
            if ($type === 'gallery') {
                // Get next sort order for gallery images
                $stmt = $this->pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM business_images WHERE business_id = ?");
                $stmt->execute([$business_id]);
                $sort_order = $stmt->fetchColumn();
            }

            // Insert into database (only using columns that exist)
            $stmt = $this->pdo->prepare("
                INSERT INTO business_images (business_id, file_name, file_path, sort_order) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $business_id,
                $file['name'],
                $file_path,
                $sort_order
            ]);

            $id = $this->pdo->lastInsertId();

            // Commit transaction
            $this->pdo->commit();

            return [
                'success' => true,
                'id' => $id,
                'file_path' => $file_path
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log("Image upload error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete an image
     */
    public function deleteImage($image_id, $business_id) {
        try {
            // Get image info
            $stmt = $this->pdo->prepare("SELECT * FROM business_images WHERE id = ? AND business_id = ?");
            $stmt->execute([$image_id, $business_id]);
            $image = $stmt->fetch();

            if (!$image) {
                throw new Exception('Image not found');
            }

            // Delete from database
            $stmt = $this->pdo->prepare("DELETE FROM business_images WHERE id = ?");
            $stmt->execute([$image_id]);

            // Delete physical file
            $file_path = dirname(__DIR__) . '/' . ltrim($image['file_path'], '/');
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            return ['success' => true];

        } catch (Exception $e) {
            error_log("Delete image error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Set an image as the main image
     */
    public function setMainImage($image_id, $business_id) {
        try {
            // Get image info
            $stmt = $this->pdo->prepare("SELECT * FROM business_images WHERE id = ? AND business_id = ?");
            $stmt->execute([$image_id, $business_id]);
            $image = $stmt->fetch();

            if (!$image) {
                throw new Exception('Image not found');
            }

            // Set all images for this business to gallery (sort_order > 0)
            $stmt = $this->pdo->prepare("UPDATE business_images SET sort_order = 1 WHERE business_id = ?");
            $stmt->execute([$business_id]);

            // Set this image as main (sort_order = 0)
            $stmt = $this->pdo->prepare("UPDATE business_images SET sort_order = 0 WHERE id = ?");
            $stmt->execute([$image_id]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log("Set main image error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get gallery images (sort_order > 0)
     */
    public function getGalleryImages($business_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM business_images 
                WHERE business_id = ? AND sort_order > 0
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$business_id]);
            
            return [
                'success' => true,
                'images' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];

        } catch (Exception $e) {
            error_log("Get gallery images error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get main image (sort_order = 0)
     */
    public function getMainImage($business_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM business_images 
                WHERE business_id = ? AND sort_order = 0
                LIMIT 1
            ");
            $stmt->execute([$business_id]);
            
            return [
                'success' => true,
                'image' => $stmt->fetch(PDO::FETCH_ASSOC)
            ];

        } catch (Exception $e) {
            error_log("Get main image error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 