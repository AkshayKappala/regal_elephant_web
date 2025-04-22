<?php
require_once __DIR__ . '/../config/database.php';
$mysqli = Database::getConnection();

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
                                <?php foreach ($menuItemsByCategory[$category] as $idx => $item): ?>
                                    <?php $itemId = 'menu-item-' . slugify($category) . '-' . $idx; ?>
                                    <div class="col">
                                        <div class="card h-100 menu-item-card explore-item-card">
                                            <div class="card-body">
                                                <h5 class="card-title menu-item-name"><?php echo $item['name']; ?></h5>
                                                <?php if (!empty($item['description'])): ?>
                                                    <p class="card-text menu-item-description"><?php echo $item['description']; ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer menu-item-footer d-flex justify-content-between align-items-center">
                                                <span class="menu-item-price fw-bold">&#8377;<?php echo number_format($item['price'], 2); ?></span>
                                                <div class="quantity-widget" data-item="<?php echo $itemId; ?>" 
                                                     data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" 
                                                     data-price="<?php echo htmlspecialchars($item['price'], ENT_QUOTES); ?>">
                                                    <button type="button" class="btn btn-sm qty-btn" data-action="decrement" disabled>-</button>
                                                    <span class="qty-value mx-2" id="<?php echo $itemId; ?>-qty">0</span>
                                                    <button type="button" class="btn btn-sm qty-btn" data-action="increment">+</button>
                                                </div>
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
                             <?php foreach ($menuItemsByCategory[$category] as $idx => $item): ?>
                                 <?php $itemId = 'menu-item-' . slugify($category) . '-other-' . $idx; ?>
                                 <div class="col">
                                     <div class="card h-100 menu-item-card explore-item-card">
                                         <div class="card-body">
                                             <h5 class="card-title menu-item-name"><?php echo $item['name']; ?></h5>
                                             <?php if (!empty($item['description'])): ?>
                                                 <p class="card-text menu-item-description"><?php echo $item['description']; ?></p>
                                             <?php endif; ?>
                                         </div>
                                         <div class="card-footer menu-item-footer d-flex justify-content-between align-items-center">
                                             <span class="menu-item-price fw-bold">&#8377;<?php echo number_format($item['price'], 2); ?></span>
                                             <div class="quantity-widget" data-item="<?php echo $itemId; ?>" 
                                                  data-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>" 
                                                  data-price="<?php echo htmlspecialchars($item['price'], ENT_QUOTES); ?>">
                                                 <button type="button" class="btn btn-sm qty-btn" data-action="decrement" disabled>-</button>
                                                 <span class="qty-value mx-2" id="<?php echo $itemId; ?>-qty">0</span>
                                                 <button type="button" class="btn btn-sm qty-btn" data-action="increment">+</button>
                                             </div>
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

    <button id="goToTopBtn" title="Go to top" class="btn btn-custom btn-go-top">
        &uarr; 
    </button>
</div>
