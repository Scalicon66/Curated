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

