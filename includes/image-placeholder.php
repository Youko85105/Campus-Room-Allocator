<?php
/**
 * Dynamic Image Placeholder Generator
 * Generates placeholder images using SVG
 */

function generatePlaceholder($width = 400, $height = 300, $text = '', $bgColor = '#667eea', $textColor = '#ffffff') {
    // Default text based on dimensions
    if (empty($text)) {
        $text = $width . ' x ' . $height;
    }
    
    // Calculate font size based on image size
    $fontSize = min($width, $height) / 10;
    
    // Create SVG
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $bgColor . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . adjustBrightness($bgColor, -20) . ';stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="' . $width . '" height="' . $height . '" fill="url(#grad)"/>
        <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="' . $fontSize . '" 
              fill="' . $textColor . '" text-anchor="middle" dominant-baseline="middle" opacity="0.8">
            ' . htmlspecialchars($text) . '
        </text>
    </svg>';
    
    return $svg;
}

function adjustBrightness($hexColor, $adjustPercent) {
    $hexColor = ltrim($hexColor, '#');
    
    if (strlen($hexColor) == 3) {
        $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
    }
    
    $hexColor = array_map('hexdec', str_split($hexColor, 2));
    
    foreach ($hexColor as &$color) {
        $adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
        $adjustAmount = ceil($adjustableLimit * $adjustPercent / 100);
        $color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
    }
    
    return '#' . implode('', $hexColor);
}

function getRoomTypeImage($roomType) {
    $images = [
        'Common Room' => [
            'color' => '#667eea',
            'icon' => '🛏️',
            'text' => 'Common Room'
        ],
        'Double Room' => [
            'color' => '#764ba2',
            'icon' => '🛏️🛏️',
            'text' => 'Double Room'
        ],
        'Single Room' => [
            'color' => '#f093fb',
            'icon' => '🛏️',
            'text' => 'Single Room'
        ]
    ];
    
    $config = $images[$roomType] ?? [
        'color' => '#6c757d',
        'icon' => '🏠',
        'text' => $roomType
    ];
    
    return 'data:image/svg+xml;base64,' . base64_encode(
        generatePlaceholder(400, 300, $config['icon'] . ' ' . $config['text'], $config['color'])
    );
}

function getProfilePlaceholder($initials = 'U', $color = '#667eea') {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:' . $color . ';stop-opacity:1" />
                <stop offset="100%" style="stop-color:' . adjustBrightness($color, -20) . ';stop-opacity:1" />
            </linearGradient>
        </defs>
        <circle cx="100" cy="100" r="100" fill="url(#grad)"/>
        <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="70" font-weight="bold"
              fill="#ffffff" text-anchor="middle" dominant-baseline="middle">
            ' . strtoupper(htmlspecialchars($initials)) . '
        </text>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

function getBuildingPlaceholder($buildingName) {
    $colors = [
        'Building A' => '#667eea',
        'Building B' => '#764ba2',
        'Building C' => '#f093fb',
        'Building D' => '#4facfe'
    ];
    
    $color = $colors[$buildingName] ?? '#6c757d';
    
    return 'data:image/svg+xml;base64,' . base64_encode(
        generatePlaceholder(600, 400, '🏢 ' . $buildingName, $color)
    );
}

// Handle direct image requests
if (isset($_GET['type']) && isset($_GET['name'])) {
    header('Content-Type: image/svg+xml');
    
    switch ($_GET['type']) {
        case 'room':
            echo generatePlaceholder(400, 300, '🛏️ ' . $_GET['name'], '#667eea');
            break;
        case 'building':
            echo generatePlaceholder(600, 400, '🏢 ' . $_GET['name'], '#764ba2');
            break;
        case 'profile':
            echo generatePlaceholder(200, 200, $_GET['name'], '#f093fb');
            break;
        default:
            echo generatePlaceholder(400, 300, 'Image');
    }
    exit;
}
?>
