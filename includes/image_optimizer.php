<?php
/**
 * Image Optimizer for JShuk
 * Handles image compression, resizing, and WebP conversion
 */

class ImageOptimizer {
    private $upload_path;
    private $max_width;
    private $max_height;
    private $quality;
    
    public function __construct($upload_path = null, $max_width = 1200, $max_height = 1200, $quality = 85) {
        $this->upload_path = $upload_path ?: __DIR__ . '/../uploads/';
        $this->max_width = $max_width;
        $this->max_height = $max_height;
        $this->quality = $quality;
    }
    
    /**
     * Optimize uploaded image
     */
    public function optimize($file_path, $output_path = null) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $output_path = $output_path ?: $file_path;
        $image_info = getimagesize($file_path);
        
        if (!$image_info) {
            return false;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Calculate new dimensions
        $new_dimensions = $this->calculateDimensions($width, $height);
        
        // Create image resource
        $source = $this->createImageResource($file_path, $mime_type);
        if (!$source) {
            return false;
        }
        
        // Create new image
        $new_image = imagecreatetruecolor($new_dimensions['width'], $new_dimensions['height']);
        
        // Preserve transparency for PNG
        if ($mime_type === 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefill($new_image, 0, 0, $transparent);
        }
        
        // Resize image
        imagecopyresampled(
            $new_image, $source,
            0, 0, 0, 0,
            $new_dimensions['width'], $new_dimensions['height'],
            $width, $height
        );
        
        // Save optimized image
        $result = $this->saveImage($new_image, $output_path, $mime_type);
        
        // Clean up
        imagedestroy($source);
        imagedestroy($new_image);
        
        return $result;
    }
    
    /**
     * Calculate new dimensions maintaining aspect ratio
     */
    private function calculateDimensions($width, $height) {
        if ($width <= $this->max_width && $height <= $this->max_height) {
            return ['width' => $width, 'height' => $height];
        }
        
        $ratio = min($this->max_width / $width, $this->max_height / $height);
        
        return [
            'width' => round($width * $ratio),
            'height' => round($height * $ratio)
        ];
    }
    
    /**
     * Create image resource from file
     */
    private function createImageResource($file_path, $mime_type) {
        switch ($mime_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($file_path);
            case 'image/png':
                return imagecreatefrompng($file_path);
            case 'image/gif':
                return imagecreatefromgif($file_path);
            case 'image/webp':
                return imagecreatefromwebp($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Save image to file
     */
    private function saveImage($image, $file_path, $mime_type) {
        $dir = dirname($file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        switch ($mime_type) {
            case 'image/jpeg':
                return imagejpeg($image, $file_path, $this->quality);
            case 'image/png':
                return imagepng($image, $file_path, round($this->quality / 10));
            case 'image/gif':
                return imagegif($image, $file_path);
            case 'image/webp':
                return imagewebp($image, $file_path, $this->quality);
            default:
                return false;
        }
    }
    
    /**
     * Convert image to WebP format
     */
    public function convertToWebP($file_path, $output_path = null) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }
        
        $source = $this->createImageResource($file_path, $image_info['mime']);
        if (!$source) {
            return false;
        }
        
        $output_path = $output_path ?: str_replace(['.jpg', '.jpeg', '.png', '.gif'], '.webp', $file_path);
        
        $result = imagewebp($source, $output_path, $this->quality);
        imagedestroy($source);
        
        return $result ? $output_path : false;
    }
    
    /**
     * Generate thumbnail
     */
    public function createThumbnail($file_path, $thumb_path, $thumb_width = 300, $thumb_height = 300) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }
        
        $width = $image_info[0];
        $height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Calculate thumbnail dimensions
        $ratio = min($thumb_width / $width, $thumb_height / $height);
        $thumb_width = round($width * $ratio);
        $thumb_height = round($height * $ratio);
        
        $source = $this->createImageResource($file_path, $mime_type);
        if (!$source) {
            return false;
        }
        
        $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);
        
        // Preserve transparency
        if ($mime_type === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
            imagefill($thumbnail, 0, 0, $transparent);
        }
        
        imagecopyresampled(
            $thumbnail, $source,
            0, 0, 0, 0,
            $thumb_width, $thumb_height,
            $width, $height
        );
        
        $result = $this->saveImage($thumbnail, $thumb_path, $mime_type);
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return $result;
    }
    
    /**
     * Get optimized image URL
     */
    public function getOptimizedUrl($original_path, $size = 'medium') {
        $path_parts = pathinfo($original_path);
        $optimized_path = $path_parts['dirname'] . '/' . $path_parts['filename'] . '_' . $size . '.' . $path_parts['extension'];
        
        if (file_exists($optimized_path)) {
            return $optimized_path;
        }
        
        return $original_path;
    }
}

// Initialize image optimizer
$image_optimizer = new ImageOptimizer();

/**
 * Helper functions for image optimization
 */
function optimize_image($file_path, $output_path = null) {
    global $image_optimizer;
    return $image_optimizer->optimize($file_path, $output_path);
}

function convert_to_webp($file_path, $output_path = null) {
    global $image_optimizer;
    return $image_optimizer->convertToWebP($file_path, $output_path);
}

function create_thumbnail($file_path, $thumb_path, $width = 300, $height = 300) {
    global $image_optimizer;
    return $image_optimizer->createThumbnail($file_path, $thumb_path, $width, $height);
}

function get_optimized_image_url($original_path, $size = 'medium') {
    global $image_optimizer;
    return $image_optimizer->getOptimizedUrl($original_path, $size);
} 