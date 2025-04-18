<?php
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

// Define the structure for the rows and their categories
$categoryGroups = [
    "Starters" => ["Veg Starter", "Non-Veg Starter", "Veg Tandoori", "Non-Veg Tandoori"],
    "Main Course" => ["Soup", "Veg Entree", "Non-Veg Entree", "Noodles", "Fried Rice", "Veg Biryani"],
    "Breads" => ["Bread"],
    "Desserts & Beverages" => ["Ice Cream", "Milkshake", "Beverage"]
];

// Fetch all distinct categories from the database that are in our defined groups
$allRelevantCategories = [];
$flatCategoryList = array_merge(...array_values($categoryGroups));

if (!empty($flatCategoryList)) {
    $placeholders = implode(',', array_fill(0, count($flatCategoryList), '?'));
    $types = str_repeat('s', count($flatCategoryList));

    try {
        $query = "SELECT DISTINCT category FROM food_items WHERE category IN ($placeholders)";
        $stmt = $mysqli->prepare($query);

        if ($stmt) {
            $stmt->bind_param($types, ...$flatCategoryList);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $allRelevantCategories[] = htmlspecialchars($row['category']);
            }
            $stmt->close();
        } else {
             error_log("Prepare statement failed: " . $mysqli->error);
             echo "<p class='text-danger text-center'>Error preparing database query.</p>";
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        echo "<p class='text-danger text-center'>Sorry, we couldn't load the categories at this time.</p>";
    }
} else {
     echo "<p class='text-info text-center'>No category groups defined.</p>";
}

// Prepare categories for display based on the desired layout
$layoutGroups = [
    'Starters' => [],
    'Main Course' => [],
    'Breads' => [],
    'Desserts & Beverages' => []
];

foreach ($categoryGroups as $label => $categoriesInGroup) {
    // Filter categories that actually exist in the database
    $displayCategories = array_intersect($allRelevantCategories, $categoriesInGroup);

    // Maintain the defined order
    $displayCategoriesOrdered = [];
    foreach ($categoriesInGroup as $orderedCategory) {
        if (in_array($orderedCategory, $displayCategories)) {
            $displayCategoriesOrdered[] = $orderedCategory;
        }
    }

    // Store the ordered categories under the correct label if they exist
    if (!empty($displayCategoriesOrdered) && isset($layoutGroups[$label])) {
        $layoutGroups[$label] = $displayCategoriesOrdered;
    }
}

// Helper function to render a category row (horizontal scroll)
function renderCategoryRow($categories) {
    if (empty($categories)) return;
    echo '<div class="row category-row flex-nowrap overflow-auto mb-4 pb-3">';
    foreach ($categories as $category) {
        echo '<div class="col-auto category-card-col">';
        echo '<div class="card h-100 shadow-sm category-card">';
        echo '<div class="card-body d-flex align-items-center justify-content-center p-3">';
        echo '<h5 class="card-title text-center mb-0">' . $category . '</h5>';
        echo '</div></div></div>';
    }
    echo '</div>';
}

?>

<div class="container py-4">
    <div id="category-content" class="w-100">

        <!-- Row 1: Starters -->
        <?php if (!empty($layoutGroups['Starters'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mt-4 mb-3 category-label">Starters</h3>
                    <?php renderCategoryRow($layoutGroups['Starters']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Row 2: Main Course -->
        <?php if (!empty($layoutGroups['Main Course'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mt-4 mb-3 category-label">Main Course</h3>
                    <?php renderCategoryRow($layoutGroups['Main Course']); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Row 3: Breads and Desserts/Beverages -->
        <?php if (!empty($layoutGroups['Breads']) || !empty($layoutGroups['Desserts & Beverages'])): ?>
            <div class="row mb-4">
                <!-- Column 1: Breads -->
                <?php if (!empty($layoutGroups['Breads'])): ?>
                    <div class="col-md-6">
                        <h3 class="mt-4 mb-3 category-label">Breads</h3>
                        <?php renderCategoryRow($layoutGroups['Breads']); ?>
                    </div>
                <?php endif; ?>

                <!-- Column 2: Desserts & Beverages -->
                <?php if (!empty($layoutGroups['Desserts & Beverages'])): ?>
                    <div class="col-md-6">
                        <h3 class="mt-4 mb-3 category-label">Desserts & Beverages</h3>
                        <?php renderCategoryRow($layoutGroups['Desserts & Beverages']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
