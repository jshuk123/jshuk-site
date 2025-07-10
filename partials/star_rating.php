<?php
/**
 * Star Rating Component
 * Displays and handles star ratings (1-5 stars)
 * Version: 1.2
 */

// Get parameters
$rating = $rating ?? 0;
$readonly = $readonly ?? false;
$size = $size ?? 'normal'; // small, normal, large
$show_number = $show_number ?? false;
$business_id = $business_id ?? null;
$form_id = $form_id ?? 'review-form';

// Size classes
$size_classes = [
    'small' => 'fa-sm',
    'normal' => '',
    'large' => 'fa-lg'
];

$star_class = $size_classes[$size] ?? '';
?>

<?php if ($readonly): ?>
    <!-- Display Only Stars -->
    <div class="star-rating-display">
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $star_class; ?> <?php echo $i <= $rating ? 'text-warning' : 'text-muted'; ?>"></i>
            <?php endfor; ?>
        </div>
        <?php if ($show_number): ?>
            <span class="rating-number ms-2"><?php echo number_format($rating, 1); ?></span>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Interactive Star Rating Input -->
    <div class="star-rating-input" data-business-id="<?php echo $business_id; ?>">
        <div class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <input type="radio" name="rating" value="<?php echo $i; ?>" 
                       id="star<?php echo $i; ?>_<?php echo $business_id; ?>" 
                       class="star-input" <?php echo $i == $rating ? 'checked' : ''; ?>>
                <label for="star<?php echo $i; ?>_<?php echo $business_id; ?>" 
                       class="star-label <?php echo $star_class; ?>">
                    <i class="fas fa-star"></i>
                </label>
            <?php endfor; ?>
        </div>
        <?php if ($show_number): ?>
            <span class="rating-number ms-2"><?php echo $rating ? number_format($rating, 1) : '0.0'; ?></span>
        <?php endif; ?>
    </div>

    <style>
    .star-rating-input {
        display: inline-flex;
        align-items: center;
    }
    
    .star-rating-input .stars {
        display: flex;
        flex-direction: row-reverse;
        gap: 2px;
    }
    
    .star-input {
        display: none;
    }
    
    .star-label {
        cursor: pointer;
        color: #ddd;
        transition: color 0.2s ease;
        font-size: 1.2em;
    }
    
    .star-label:hover,
    .star-label:hover ~ .star-label,
    .star-input:checked ~ .star-label {
        color: #ffc107;
    }
    
    .star-rating-display .stars {
        display: inline-flex;
        gap: 2px;
    }
    
    .rating-number {
        font-weight: bold;
        color: #333;
    }
    
    /* Size variations */
    .star-label.fa-sm {
        font-size: 0.9em;
    }
    
    .star-label.fa-lg {
        font-size: 1.5em;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const starInputs = document.querySelectorAll('.star-rating-input input[type="radio"]');
        const ratingNumbers = document.querySelectorAll('.rating-number');
        
        starInputs.forEach(input => {
            input.addEventListener('change', function() {
                const rating = parseInt(this.value);
                const container = this.closest('.star-rating-input');
                const numberSpan = container.querySelector('.rating-number');
                
                if (numberSpan) {
                    numberSpan.textContent = rating.toFixed(1);
                }
                
                // Update form hidden field if it exists
                const form = document.getElementById('<?php echo $form_id; ?>');
                if (form) {
                    let hiddenField = form.querySelector('input[name="rating"]');
                    if (!hiddenField) {
                        hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = 'rating';
                        form.appendChild(hiddenField);
                    }
                    hiddenField.value = rating;
                }
            });
        });
    });
    </script>
<?php endif; ?> 