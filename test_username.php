<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require 'dairy_farm_backend/config/bootstrap.php';

// Test username generation
function generateUniqueUsername($name, $email) {
    // Start with the name, clean it up
    $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $name);
    $baseUsername = strtolower(substr($baseUsername, 0, 20)); // Limit length
    
    if (empty($baseUsername)) {
        // Fallback to email prefix if name is empty
        $baseUsername = strtolower(explode('@', $email)[0]);
        $baseUsername = preg_replace('/[^a-zA-Z0-9]/', '', $baseUsername);
    }
    
    $username = $baseUsername;
    $counter = 1;
    
    // Check if username exists and increment counter if needed
    while (true) {
        $stmt = getConnection()->prepare('SELECT COUNT(*) FROM Worker WHERE Worker = ?');
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            break; // Username is available
        }
        
        $username = $baseUsername . $counter;
        $counter++;
        
        // Prevent infinite loop
        if ($counter > 100) {
            $username = $baseUsername . '_' . time();
            break;
        }
    }
    
    return $username;
}

echo "Testing username generation:\n";
echo "John Doe -> " . generateUniqueUsername("John Doe", "john@example.com") . "\n";
echo "Jane Smith -> " . generateUniqueUsername("Jane Smith", "jane@example.com") . "\n";
echo "Test User -> " . generateUniqueUsername("Test User", "test@example.com") . "\n";
?>