<?php
require_once __DIR__ . '/../../config/config.php'; // adjust path as needed

$descriptions = [
  'Accommodation & Short-Term Rentals' => 'Trusted places for guests, travelers, and community hosting needs',
  'Advertising & Promotion' => 'Services to help your business get seen and remembered',
  'Baby & Maternity' => 'Support for parents, babies, and growing Jewish families',
  'Business Services (B2B)' => 'Tools and services to help other businesses succeed and scale',
  'Cleaning & Domestic Help' => 'Help keeping homes clean, safe, and welcoming',
  'Education, Kids & Health' => 'Programs for learning, wellness, and child development',
  'Estate Agents & Property' => 'Agents and professionals to help you buy, rent, or manage property',
  'Fashion & Tailoring' => 'Clothing, custom tailoring, and personal style with Jewish flair',
  'Food & Hospitality' => 'Hospitality services from servers to party staff and event kitchens',
  'Gifts & Food Platters' => 'Curated Jewish gifts and delicious shareable food experiences',
  'Health & Wellness' => 'Holistic, mental, and physical wellness services for all ages',
  'Home & Trades' => 'Skilled home repairs, improvements, and maintenance by trusted professionals',
  'Jewellery & Accessories' => 'Elegant Judaica jewelry and stylish accessories for every occasion',
  'Jewish Art & Decor' => 'Jewish-themed art, prints, and decor for home or simcha',
  'Judaica & Religious Items' => 'Tefillin, mezuzahs, challah covers, and all sacred essentials',
  'Personal & Admin Services' => 'Everyday support services like translation, typing, resumes, and more',
  'Pets & Animals' => 'Services and products for your furry, feathered, or fishy friends',
  'Photography & Simchas' => 'Photographers who capture your moments and make memories last',
  'Professional & Business Services' => 'Accountants, consultants, legal experts, and other pros who get results',
  'Real Estate & Housing' => 'Buying, renting, or managing properties with trusted local agents',
  'Removals & Storage' => 'Safe, reliable moving help and storage options for transitions',
  'Retail, Gifts & Style' => 'Boutique items and gifts with Jewish values and visual appeal',
  'School & Nurseries' => 'Educational environments that nurture faith, growth, and development',
  'Simcha Planning & Hire' => 'Everything you need to plan, decorate, and host your simcha',
  'Simchas, Events & Community' => 'Listings for local happenings, simchas, and community gatherings',
  'Technology & Digital Services' => 'Web, IT, and tech services from developers to support teams',
  'Trades & Construction' => 'Builders, handymen, plumbers, electricians — all verified professionals',
  'Travel & Transport' => 'Ride services, travel agents, and transport help for UK and abroad'
];

// Loop through descriptions and update if blank
$updated = 0;

foreach ($descriptions as $name => $desc) {
    $stmt = $pdo->prepare("SELECT id, description FROM business_categories WHERE name = ?");
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    if ($row && empty(trim($row['description']))) {
        $update = $pdo->prepare("UPDATE business_categories SET description = ? WHERE id = ?");
        $update->execute([$desc, $row['id']]);
        echo "✅ Updated description for: $name<br>";
        $updated++;
    }
}

if ($updated === 0) {
    echo "✅ All descriptions already filled.";
} else {
    echo "<br>✨ $updated descriptions updated successfully.";
} 