<?php
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

// Define category groups (same as explore.php for consistent ordering)
$categoryGroups = [
    "Starters" => ["Veg Starter", "Non-Veg Starter", "Veg Tandoori", "Non-Veg Tandoori"],
    "Main Course" => ["Veg Soup","Non-Veg Soup", "Veg Entree", "Non-Veg Entree", "Veg Noodles", "Non-Veg Noodles", "Veg Fried Rice", "Non-Veg Fried Rice", "Veg Biryani", "Non-Veg Biryani"],
    "Breads" => ["Bread"],
    "Desserts & Beverages" => ["Ice Cream", "Milkshake", "Beverage"]
];

$menuItemsByCategory = [];
$allCategoriesInMenu = [];

try {
    $query = "SELECT name, description, price, category FROM food_items ORDER BY category, name";
    $result = $mysqli->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $category = htmlspecialchars($row['category']);
            if (!isset($menuItemsByCategory[$category])) {
                $menuItemsByCategory[$category] = [];
            }
            $menuItemsByCategory[$category][] = [
                'name' => htmlspecialchars($row['name']),
                'description' => htmlspecialchars($row['description']),
                'price' => htmlspecialchars($row['price'])
            ];
            if (!in_array($category, $allCategoriesInMenu)) {
                $allCategoriesInMenu[] = $category;
            }
        }
        $result->free();
    } else {
        error_log("Error fetching menu items: " . $mysqli->error);
        echo "<p class='text-danger text-center'>Error loading menu items.</p>";
    }
} catch (mysqli_sql_exception $e) {
    error_log("Database error fetching menu items: " . $e->getMessage());
    echo "<p class='text-danger text-center'>Sorry, we couldn't load the menu at this time.</p>";
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

?>

<div class="container py-4">
    <h1 class="text-center mb-5 title-menu">Full Menu</h1>

    <?php foreach ($categoryGroups as $groupLabel => $categoriesInGroup): ?>
        <?php
            $groupHasItems = false;
            foreach ($categoriesInGroup as $category) {
                if (isset($menuItemsByCategory[$category]) && !empty($menuItemsByCategory[$category])) {
                    $groupHasItems = true;
                    break;
                }
            }
        ?>

        <?php if ($groupHasItems): ?>
            <div class="mb-5">
                <h2 class="category-group-label mb-4"><?php echo htmlspecialchars($groupLabel); ?></h2>

                <?php foreach ($categoriesInGroup as $category): ?>
                    <?php if (isset($menuItemsByCategory[$category]) && !empty($menuItemsByCategory[$category])): ?>
                        <?php $categoryId = 'category-' . slugify($category); ?>
                        <div id="<?php echo $categoryId; ?>" class="mb-4 category-section">
                            <h3 class="category-label mb-3"><?php echo htmlspecialchars($category); ?></h3>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                                <?php foreach ($menuItemsByCategory[$category] as $item): ?>
                                    <div class="col">
                                        <div class="card h-100 menu-item-card">
                                            <div class="card-body">
                                                <h5 class="card-title menu-item-name"><?php echo $item['name']; ?></h5>
                                                <?php if (!empty($item['description'])): ?>
                                                    <p class="card-text menu-item-description"><?php echo $item['description']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer menu-item-footer">
                                                <span class="menu-item-price">$<?php echo number_format($item['price'], 2); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php
        $uncategorizedItemsExist = false;
        foreach ($allCategoriesInMenu as $category) {
            $isInGroup = false;
            foreach ($categoryGroups as $group) {
                if (in_array($category, $group)) {
                    $isInGroup = true;
                    break;
                }
            }
            if (!$isInGroup && isset($menuItemsByCategory[$category]) && !empty($menuItemsByCategory[$category])) {
                $uncategorizedItemsExist = true;
                break;
            }
        }
    ?>

    <?php if ($uncategorizedItemsExist): ?>
        <div class="mb-5">
            <h2 class="category-group-label mb-4">Other Items</h2>
            <?php foreach ($allCategoriesInMenu as $category): ?>
                 <?php
                    $isInGroup = false;
                    foreach ($categoryGroups as $group) {
                        if (in_array($category, $group)) {
                            $isInGroup = true;
                            break;
                        }
                    }
                 ?>
                <?php if (!$isInGroup && isset($menuItemsByCategory[$category]) && !empty($menuItemsByCategory[$category])): ?>
                     <?php $categoryId = 'category-' . slugify($category); ?>
                     <div id="<?php echo $categoryId; ?>" class="mb-4 category-section">
                         <h3 class="category-label mb-3"><?php echo htmlspecialchars($category); ?></h3>
                         <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                             <?php foreach ($menuItemsByCategory[$category] as $item): ?>
                                 <div class="col">
                                     <div class="card h-100 menu-item-card">
                                         <div class="card-body">
                                             <h5 class="card-title menu-item-name"><?php echo $item['name']; ?></h5>
                                             <?php if (!empty($item['description'])): ?>
                                                 <p class="card-text menu-item-description"><?php echo $item['description']; ?></p>
                                             <?php endif; ?>
                                         </div>
                                         <div class="card-footer menu-item-footer">
                                             <span class="menu-item-price">$<?php echo number_format($item['price'], 2); ?></span>
                                         </div>
                                     </div>
                                 </div>
                             <?php endforeach; ?>
                         </div>
                     </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <?php if (empty($menuItemsByCategory)): ?>
        <p class="text-center text-info">No menu items are currently available.</p>
    <?php endif; ?>

</div>

<style>
    .title-menu {
        font-family: "League Gothic", sans-serif;
        font-size: 5rem;
        color: #eadab0;
    }
    .category-group-label {
        font-family: "Marko One", serif;
        color: #f5e7c8;
        font-size: 2.5rem;
        border-bottom: 2px solid #a04b25;
        padding-bottom: 0.5rem;
    }
    .category-label {
        color: #eadab0;
        font-family: "Marko One", serif;
        font-size: 2rem;
    }
    .menu-item-card {
        background-color: #004f55;
        border: 1px solid #a04b25;
        color: #eadab0;
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .menu-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(234, 218, 176, 0.2);
    }
    .menu-item-name {
        font-family: "Fredoka", sans-serif;
        font-weight: bold;
        color: #f5e7c8;
    }
    .menu-item-description {
        font-size: 0.9rem;
        color: #d4c3a2;
    }
    .menu-item-footer {
        background-color: rgba(0, 0, 0, 0.2);
        border-top: 1px solid #a04b25;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .menu-item-price {
        font-weight: bold;
        font-size: 1.1rem;
    }
</style>
