<?php
require_once 'config/database.php';

echo "<h2>Database Connection Test</h2>";

if (testConnection()) {
    echo "<p style='color: green;'>✅ Database connected successfully!</p>";
    
    // Test: Count users
    $sql = "SELECT COUNT(*) as total FROM users";
    $result = fetchOne($sql);
    echo "<p>Total users in database: <strong>" . $result['total'] . "</strong></p>";
    
    // Test: List room types
    $sql = "SELECT * FROM room_types";
    $roomTypes = fetchAll($sql);
    echo "<h3>Room Types:</h3><ul>";
    foreach ($roomTypes as $type) {
        echo "<li>{$type['type_name']} - Capacity: {$type['capacity']}</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green;'>✅ Database is working perfectly!</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed!</p>";
}
?>
```

3. Save as: `C:\xampp\htdocs\csc433\test_connection.php`

---

## 🧪 **Test the Connection**

1. **Make sure Apache and MySQL are running** in XAMPP Control Panel (both should be green)

2. **Open your browser** and go to:
```
   http://localhost/csc433/test_connection.php
```

3. **You should see:**
   - ✅ Database connected successfully!
   - Total users in database: **6**
   - Room Types listed (Common Room, Double Room, Single Room)

---

## 📸 **What You Should See:**

If successful, you'll see something like:
```
Database Connection Test

✅ Database connected successfully!

Total users in database: 6

Room Types:
- Common Room - Capacity: 6
- Double Room - Capacity: 2
- Single Room - Capacity: 1

✅ Database is working perfectly!