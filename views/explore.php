<?php
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

// Fetch distinct categories from the food_items table
$categories = [];
try {
    $query = "SELECT DISTINCT category FROM food_items";
    $result = $mysqli->query($query);

    while ($row = $result->fetch_assoc()) {
        $categories[] = htmlspecialchars($row['category']);
    }

    $result->free();
} catch (mysqli_sql_exception $e) {
    echo "Error fetching categories: " . $e->getMessage();
}
?>

<div class="container">
    <h1>Explore</h1>

    <div id="content">
        <h3>Food Categories</h3>
        <ul>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                    <li><?php echo $category; ?></li>
                <?php endforeach; ?>
            <?php else: ?>
                <li>No categories found.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>
