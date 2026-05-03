<?php
// Shared helpers + session bootstrap. Include at the top of every page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database connection (must be defined before products.php) ---
function get_db_connection(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host = '127.0.0.1';
    $db   = 'shop_db';
    $user = 'root';
    $pass = ''; // Default XAMPP/WAMP empty password
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        return $pdo;
    } catch (\PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

require_once __DIR__ . '/products.php';

// --- Multi-Language System ---
function lang_init(): void {
    if (!isset($_SESSION['lang'])) {
        $_SESSION['lang'] = 'en';
    }
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
        $_SESSION['lang'] = $_GET['lang'];
    }
}

function lang_current(): string {
    return $_SESSION['lang'] ?? 'en';
}

function t(string $key): string {
    static $translations = [
        'en' => [
            'shop_all' => 'Shop All',
            'admin_panel' => 'Admin Panel',
            'profile' => 'Profile',
            'logout' => 'Logout',
            'login' => 'Log In',
            'signup' => 'Sign Up',
            'hero_title' => 'Essential Objects.',
            'hero_desc' => 'Thoughtfully designed, carefully selected tools for your daily rituals.',
            'search_placeholder' => 'Search products...',
            'cart' => 'Cart',
            'add_to_cart' => 'Add to Cart',
            'in_stock' => 'in stock',
            'sold_out' => 'Sold out',
            'in_cart' => 'in cart',
            'nothing_matches' => 'Nothing matches that.',
            'try_different' => 'Try a different search term or category.',
            'price' => 'Price',
            'categories' => 'Categories',
            'all' => 'All',
        ],
        'ar' => [
            'shop_all' => 'تسوق الكل',
            'admin_panel' => 'لوحة التحكم',
            'profile' => 'الملف الشخصي',
            'logout' => 'تسجيل الخروج',
            'login' => 'تسجيل الدخول',
            'signup' => 'إنشاء حساب',
            'hero_title' => 'قطع أساسية.',
            'hero_desc' => 'أدوات مصممة بعناية ومختارة بدقة لطقوسك اليومية.',
            'search_placeholder' => 'ابحث عن المنتجات...',
            'cart' => 'السلة',
            'add_to_cart' => 'أضف إلى السلة',
            'in_stock' => 'متوفر في المخزون',
            'sold_out' => 'نفذت الكمية',
            'in_cart' => 'في السلة',
            'nothing_matches' => 'لا توجد نتائج تطابق بحثك.',
            'try_different' => 'حاول استخدام كلمات بحث مختلفة أو فئة أخرى.',
            'price' => 'السعر',
            'categories' => 'الفئات',
            'all' => 'الكل',
        ]
    ];
    $lang = lang_current();
    return $translations[$lang][$key] ?? $key;
}

lang_init();

// --- Output helpers ---
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(float $n): string {
    return '$' . number_format($n, 2);
}

function url(string $path): string {
    return $path; // single hook for future base-path changes
}

// --- Product lookup ---
function find_product(string $id): ?array {
    global $PRODUCTS;
    foreach ($PRODUCTS as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function all_categories(): array {
    global $PRODUCTS;
    $cats = [];
    foreach ($PRODUCTS as $p) {
        $cats[$p['category']] = true;
    }
    return array_keys($cats);
}

// --- Cart (lives in $_SESSION['cart'] as id => qty) ---
function cart_items(): array {
    return $_SESSION['cart'] ?? [];
}

function cart_save(array $cart): void {
    $_SESSION['cart'] = $cart;
}

function cart_add(string $productId, int $qty = 1): void {
    $product = find_product($productId);
    if (!$product || !$product['inStock'] || $qty < 1) return;
    $cart = cart_items();
    $cart[$productId] = ($cart[$productId] ?? 0) + $qty;
    cart_save($cart);
}

function cart_update(string $productId, int $qty): void {
    $cart = cart_items();
    if ($qty < 1) {
        unset($cart[$productId]);
    } else {
        if (find_product($productId)) {
            $cart[$productId] = $qty;
        }
    }
    cart_save($cart);
}

function cart_remove(string $productId): void {
    $cart = cart_items();
    unset($cart[$productId]);
    cart_save($cart);
}

function cart_clear(): void {
    cart_save([]);
}

function cart_count(): int {
    return array_sum(cart_items());
}

function cart_detailed(): array {
    $rows = [];
    foreach (cart_items() as $id => $qty) {
        $product = find_product($id);
        if ($product) {
            $rows[] = [
                'product' => $product,
                'qty' => $qty,
                'lineTotal' => $product['price'] * $qty,
            ];
        }
    }
    return $rows;
}

function cart_subtotal(): float {
    $sum = 0.0;
    foreach (cart_detailed() as $row) {
        $sum += $row['lineTotal'];
    }
    return $sum;
}

function shipping_cost(float $subtotal): float {
    return $subtotal >= 100 ? 0.0 : 8.00;
}

// --- Flash messages (toast) ---
function flash_set(string $msg): void {
    $_SESSION['flash'] = $msg;
}
function flash_pop(): ?string {
    if (!isset($_SESSION['flash'])) return null;
    $m = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $m;
}

// --- CSRF (lightweight) ---
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_check(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['csrf'] ?? '', $token);
}

function redirect(string $location): void {
    header('Location: ' . $location);
    exit;
}

// --- Authentication ---
function user_register(string $username, string $email, string $password, int $age, string $gender, string $type): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, age, gender, type) VALUES (?, ?, ?, ?, ?, ?)');
    return $stmt->execute([$username, $email, $hash, $age, $gender, $type]);
}

function user_login(string $email, string $password): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        return false;
    }
    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = $user['email'];
        return true;
    }
    return false;
}

function user_current(): ?string {
    return $_SESSION['user'] ?? null;
}

function user_details(string $email): ?array {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function user_logout(): void {
    unset($_SESSION['user']);
}

function get_all_users(): array {
    $db = get_db_connection();
    $stmt = $db->query('SELECT * FROM users ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

// --- Product Management ---
function product_add(string $name, float $price, string $category, string $shortDesc, string $longDesc, string $imageUrl, int $stock): string {
    $db = get_db_connection();
    $id = 'prod-' . bin2hex(random_bytes(6));
    $stmt = $db->prepare('INSERT INTO products (id, name, price, category, short_description, long_description, image_url, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$id, $name, $price, $category, $shortDesc, $longDesc, $imageUrl, $stock]);
    return $id;
}

function product_delete(string $id): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('DELETE FROM products WHERE id = ?');
    return $stmt->execute([$id]);
}

function product_update(string $id, string $name, float $price, string $category, string $shortDesc, string $longDesc, ?string $imageUrl, int $stock): bool {
    $db = get_db_connection();
    if ($imageUrl !== null) {
        $stmt = $db->prepare('UPDATE products SET name=?, price=?, category=?, short_description=?, long_description=?, image_url=?, stock=? WHERE id=?');
        return $stmt->execute([$name, $price, $category, $shortDesc, $longDesc, $imageUrl, $stock, $id]);
    } else {
        $stmt = $db->prepare('UPDATE products SET name=?, price=?, category=?, short_description=?, long_description=?, stock=? WHERE id=?');
        return $stmt->execute([$name, $price, $category, $shortDesc, $longDesc, $stock, $id]);
    }
}

function product_find_db(string $id): ?array {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_search(string $query): array {
    $db = get_db_connection();
    $q = '%' . $query . '%';
    $stmt = $db->prepare('SELECT * FROM products WHERE name LIKE ? OR category LIKE ? OR short_description LIKE ? OR long_description LIKE ?');
    $stmt->execute([$q, $q, $q, $q]);
    return $stmt->fetchAll();
}

// --- User Management ---
function user_update_role(int $id, string $type): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('UPDATE users SET type = ? WHERE id = ?');
    return $stmt->execute([$type, $id]);
}

function user_delete(int $id): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
    return $stmt->execute([$id]);
}

// --- Email Notifications (PHPMailer) ---
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/SMTP.php';

function send_order_email(string $to, string $subject, string $message): bool {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug  = 0; // Set to 2 if you still get errors to see the log
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'skibidipipidi2@gmail.com'; 
        // IMPORTANT: Use a Gmail App Password, not your regular password
        // Get it here: https://myaccount.google.com/apppasswords
        $mail->Password   = 'zxnn whdz onng ruce'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('skibidipipidi2@gmail.com', 'Curated Store');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
    } catch (Exception $e) {
        // Error logging can go here
        return false;
    }
}

// --- Order Persistence ---
function save_order(string $orderId, ?string $userEmail, float $total, array $items, array $details): bool {
    $db = get_db_connection();
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare('INSERT INTO orders (id, user_email, full_name, phone, whatsapp, governorate, city, address, total_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $orderId, 
            $userEmail, 
            $details['name'], 
            $details['phone'], 
            $details['whatsapp'], 
            $details['governorate'], 
            $details['city'], 
            $details['address'], 
            $total
        ]);
        
        $stmtItem = $db->prepare('INSERT INTO order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)');
        foreach ($items as $item) {
            $stmtItem->execute([$orderId, $item['name'], $item['qty'], $item['price']]);
        }
        
        $db->commit();
        return true;
    } catch (PDOException $e) {
        $db->rollBack();
        return false;
    }
}

function get_user_orders(string $email): array {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT * FROM orders WHERE user_email = ? ORDER BY created_at DESC');
    $stmt->execute([$email]);
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $stmtItem = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmtItem->execute([$order['id']]);
        $order['items'] = $stmtItem->fetchAll();
    }
    
    return $orders;
}

function get_all_orders(): array {
    $db = get_db_connection();
    $stmt = $db->query('SELECT * FROM orders ORDER BY created_at DESC');
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $stmtItem = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmtItem->execute([$order['id']]);
        $order['items'] = $stmtItem->fetchAll();
    }
    
    return $orders;
}

function get_order_details(string $id): ?array {
    $db = get_db_connection();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $stmtItem = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmtItem->execute([$id]);
        $order['items'] = $stmtItem->fetchAll();
        return $order;
    }
    return null;
}

function order_update_status(string $id, string $status): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('UPDATE orders SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

function order_delete(string $id): bool {
    $db = get_db_connection();
    $stmt = $db->prepare('DELETE FROM orders WHERE id = ?');
    return $stmt->execute([$id]);
}

function order_delete_all(): bool {
    $db = get_db_connection();
    // TRUNCATE is faster and resets IDs, but DELETE is safer with transactions if needed.
    // Since we have foreign keys with ON DELETE CASCADE, deleting from orders will clean order_items.
    return $db->exec('DELETE FROM orders') !== false;
}

function get_monthly_sales(): array {
    $db = get_db_connection();
    $stmt = $db->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as total
        FROM orders
        WHERE status != 'Cancelled'
        GROUP BY month
        ORDER BY month ASC
        LIMIT 12
    ");
    return $stmt->fetchAll();
}

