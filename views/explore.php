<?php
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

$categoryGroups = [
    "Starters" => ["Veg Starter", "Non-Veg Starter", "Veg Tandoori", "Non-Veg Tandoori"],
    "Main Course" => ["Veg Soup","Non-Veg Soup", "Veg Entree", "Non-Veg Entree", "Veg Noodles", "Non-Veg Noodles", "Veg Fried Rice", "Non-Veg Fried Rice", "Veg Biryani", "Non-Veg Biryani"],
    "Breads" => ["Bread"],
    "Desserts & Beverages" => ["Ice Cream", "Milkshake", "Beverage"]
];

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

$layoutGroups = [
    'Starters' => [],
    'Main Course' => [],
    'Breads' => [],
    'Desserts & Beverages' => []
];

foreach ($categoryGroups as $label => $categoriesInGroup) {
    $displayCategories = array_intersect($allRelevantCategories, $categoriesInGroup);

    $displayCategoriesOrdered = [];
    foreach ($categoriesInGroup as $orderedCategory) {
        if (in_array($orderedCategory, $displayCategories)) {
            $displayCategoriesOrdered[] = $orderedCategory;
        }
    }

    if (!empty($displayCategoriesOrdered) && isset($layoutGroups[$label])) {
        $layoutGroups[$label] = $displayCategoriesOrdered;
    }
}

function renderCategoryRow($categories) {
    if (empty($categories)) return;
    echo '<div class="row flex-nowrap overflow-auto mb-4 pb-3 pt-1">';
    foreach ($categories as $category) {
        echo '<div class="col-auto">';
        echo '<a href="#" class="btn btn-custom m-1" data-page="menu" data-category="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</a>';
        echo '</div>';
    }
    echo '</div>';
}

function renderCategoryButton($category) {
    echo '<a href="#" class="btn btn-custom w-100 d-flex align-items-center justify-content-center" data-page="menu" data-category="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</a>';
}

?>

<div class="container py-4">
    <div id="category-content" class="w-100">

        <?php if (!empty($layoutGroups['Starters'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mt-4 mb-2 category-label">Starters</h3>
                    <?php renderCategoryRow($layoutGroups['Starters']); ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($layoutGroups['Main Course'])): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mt-4 mb-3 category-label">Main Course</h3>
                    <?php
                        $mainCourseCategories = $layoutGroups['Main Course'];
                        $firstRow = array_slice($mainCourseCategories, 0, 5);
                        $remaining = array_slice($mainCourseCategories, 5);
                    ?>

                    <div class="row mb-3">
                        <?php foreach ($firstRow as $category): ?>
                            <div class="col-6 col-sm-4 col-md-2 mb-3">
                                <?php renderCategoryButton($category); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($remaining)): ?>
                        <div class="row">
                            <?php foreach ($remaining as $category): ?>
                                <div class="col-6 col-sm-4 col-md-2 mb-3">
                                    <?php renderCategoryButton($category); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($layoutGroups['Breads']) || !empty($layoutGroups['Desserts & Beverages'])): ?>
            <div class="row mb-4">
                <?php if (!empty($layoutGroups['Breads'])): ?>
                    <div class="col-md-4">
                        <h3 class="mt-4 mb-2 category-label">Breads</h3>
                        <?php renderCategoryRow($layoutGroups['Breads']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($layoutGroups['Desserts & Beverages'])): ?>
                    <div class="col-md-8">
                        <h3 class="mt-4 mb-2 category-label">Desserts & Beverages</h3>
                        <?php renderCategoryRow($layoutGroups['Desserts & Beverages']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>
</div>
