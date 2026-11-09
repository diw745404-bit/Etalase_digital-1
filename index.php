<?php
session_start();

/* ============================================================
   DATABASE
   ============================================================ */
$db = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA foreign_keys = ON');

$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        name TEXT NOT NULL,
        role TEXT NOT NULL CHECK(role IN ('sa', 'admin', 'siswa'))
    );

    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT NOT NULL,
        image TEXT,
        link TEXT,
        demo TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id)
    );
");

/* Seed akun demo (hanya jika tabel users masih kosong) */
if ((int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
    $seed = [
        ['sa', 'Wahyu SA', 'sa'],
        ['admin', 'Admin Etalase', 'admin'],
        ['siswa', 'Siswa Demo', 'siswa'],
    ];
    $stmt = $db->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)');
    foreach ($seed as $u) {
        $stmt->execute([$u[0], password_hash('rojul', PASSWORD_DEFAULT), $u[1], $u[2]]);
    }
}

/* ============================================================
   HELPER
   ============================================================ */
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function logged()
{
    return isset($_SESSION['user']);
}

function role()
{
    return $_SESSION['user']['role'] ?? '';
}

function is_admin()
{
    return in_array(role(), ['sa', 'admin'], true);
}

function go($url = 'index.php')
{
    header('Location: ' . $url);
    exit;
}

function flash($type, $msg)
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function csrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok($token)
{
    return hash_equals($_SESSION['csrf'] ?? '', (string) $token);
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf()) . '">';
}

/* Hanya izinkan http/https agar tidak ada link "javascript:" */
function safe_url($url)
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $url)) {
        return $url;
    }
    if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url)) {
        return false;
    }
    return 'https://' . $url;
}

function delete_image($name)
{
    if (!empty($name) && is_file(__DIR__ . '/uploads/' . $name)) {
        @unlink(__DIR__ . '/uploads/' . $name);
    }
}

/* Potongan HTML yang dipakai ulang */
function cube_scene()
{
    return '
    <div class="scene" aria-hidden="true">
        <div class="cube-wrap">
            <div class="cube">
                <div class="face f-front">RPL</div>
                <div class="face f-back">XI</div>
                <div class="face f-right">UKK</div>
                <div class="face f-left">L3</div>
                <div class="face f-top">&lt;/&gt;</div>
                <div class="face f-bottom">★</div>
            </div>
        </div>
        <span class="ring r1"></span>
        <span class="ring r2"></span>
        <span class="orb o1"></span>
        <span class="orb o2"></span>
        <span class="orb o3"></span>
    </div>';
}

function page_head($bodyClass = '')
{
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etalase Digital Skills</title>
    <!-- Favicon Logo Website -->
    <link rel="icon" href="uploads/logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=3">
</head>
<body class="<?= e($bodyClass) ?>">
    <canvas id="bg-canvas" aria-hidden="true"></canvas>
    <div class="bg-glow g1"></div>
    <div class="bg-glow g2"></div>
<?php
}

function page_foot()
{
    ?>
    <script src="script.js?v=3"></script>
</body>
</html>
<?php
}

function flash_box()
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alert ' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
}

/* ============================================================
   LOGOUT & LOGIN
   ============================================================ */
if (isset($_GET['logout'])) {
    session_destroy();
    go();
}

$action = $_GET['action'] ?? '';

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        go();
    }
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $q = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
    $q->execute([$username]);
    $u = $q->fetch(PDO::FETCH_ASSOC);
    if ($u && password_verify($password, $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $u['id'],
            'username' => $u['username'],
            'name' => $u['name'],
            'role' => $u['role'],
        ];
        go();
    }
    flash('error', 'Username atau password salah.');
    go();
}

if (!logged()) {
    page_head('login-body');
    ?>
    <main class="login-wrap">
        <section class="login-visual">
            <?= cube_scene() ?>
            <div class="login-copy reveal">
                <span class="eyebrow">PORTFOLIO SISWA</span>
                <h2>Pamerkan karya digitalmu ke dunia.</h2>
                <p>Satu etalase untuk semua project, link, dan demo terbaik XI RPL.</p>
            </div>
        </section>

        <section class="login-card" data-tilt="6">
            <div class="brand">
                <img src="./uploads/fototelkom.png" alt="Logo" class="brand-logo" style="width: 36px; height: 36px; object-fit: contain;">
                <div>
                    <h1>Etalase Digital Skills</h1>
                    <p>UKK Level 3 • XI RPL</p>
                </div>
            </div>

            <h2>Masuk</h2>
            <?php flash_box(); ?>

            <form method="post" action="index.php?action=login">
                <?= csrf_field() ?>
                <label>
                    Username
                    <input name="username" required autofocus placeholder="Contoh: siswa">
                </label>
                <label>
                    Password
                    <input name="password" type="password" required placeholder="Password">
                </label>
                <button class="btn primary block" type="submit">Masuk</button>
            </form>

            <div class="demo-box">
                <b>Akun demo</b>
                <p>SA: <code>sa</code> / <code>rojul</code></p>
                <p>Admin: <code>admin</code> / <code>rojul</code></p>
                <p>Siswa: <code>siswa</code> / <code>rojul</code></p>
            </div>
        </section>
    </main>
    <?php
    page_foot();
    exit;
}

/* ============================================================
   PROJECT: SIMPAN / HAPUS
   ============================================================ */
if ($action === 'save_project' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $back = 'index.php?page=project_form' . ($id ? '&id=' . $id : '');

    if (!csrf_ok($_POST['csrf'] ?? '')) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        go($back);
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = safe_url($_POST['link'] ?? '');
    $demo = safe_url($_POST['demo'] ?? '');

    if ($title === '' || $description === '') {
        flash('error', 'Judul dan deskripsi wajib diisi.');
        go($back);
    }
    if ($link === false || $demo === false) {
        flash('error', 'Link dan demo harus berupa alamat http:// atau https://.');
        go($back);
    }

    $old = null;
    if ($id > 0) {
        $q = $db->prepare('SELECT * FROM projects WHERE id = ?');
        $q->execute([$id]);
        $old = $q->fetch(PDO::FETCH_ASSOC);
        if (!$old || (!is_admin() && (int) $old['user_id'] !== (int) $_SESSION['user']['id'])) {
            http_response_code(403);
            exit('Akses ditolak.');
        }
    }

    $imageName = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Gambar gagal diunggah. Periksa ukuran file dan konfigurasi upload PHP.');
            go($back);
        }
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true) || @getimagesize($_FILES['image']['tmp_name']) === false) {
            flash('error', 'File harus berupa gambar JPG, JPEG, PNG, WEBP, atau GIF.');
            go($back);
        }
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            flash('error', 'Folder upload tidak dapat dibuat.');
            go($back);
        }
        $imageName = uniqid('project_', true) . '.' . $ext;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . '/' . $imageName)) {
            flash('error', 'Gambar gagal disimpan. Periksa izin folder uploads.');
            go($back);
        }
    }

    if ($id > 0) {
        if ($imageName) {
            delete_image($old['image']);
        } else {
            $imageName = $old['image'];
        }
        $q = $db->prepare(
            'UPDATE projects SET title = ?, description = ?, image = ?, link = ?, demo = ? WHERE id = ?'
        );
        $q->execute([$title, $description, $imageName, $link, $demo, $id]);
        flash('success', 'Project berhasil diperbarui.');
    } else {
        $q = $db->prepare(
            'INSERT INTO projects (user_id, title, description, image, link, demo) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $q->execute([$_SESSION['user']['id'], $title, $description, $imageName, $link, $demo]);
        flash('success', 'Project berhasil ditambahkan.');
    }
    go('index.php?page=projects');
}

if ($action === 'delete_project' && isset($_GET['id'])) {
    if (!csrf_ok($_GET['t'] ?? '')) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        go('index.php?page=projects');
    }
    $id = (int) $_GET['id'];
    $q = $db->prepare('SELECT * FROM projects WHERE id = ?');
    $q->execute([$id]);
    $p = $q->fetch(PDO::FETCH_ASSOC);
    if ($p && (is_admin() || (int) $p['user_id'] === (int) $_SESSION['user']['id'])) {
        delete_image($p['image']);
        $db->prepare('DELETE FROM projects WHERE id = ?')->execute([$id]);
        flash('success', 'Project dihapus.');
    }
    go('index.php?page=projects');
}

/* ============================================================
   USER: TAMBAH / EDIT / HAPUS (khusus SA)
   ============================================================ */
if ($action === 'save_user' && role() === 'sa' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok($_POST['csrf'] ?? '')) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        go('index.php?page=users');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $newpass = $_POST['password'] ?? '';
    $newrole = $_POST['role'] ?? 'siswa';
    if (!in_array($newrole, ['sa', 'admin', 'siswa'], true)) {
        $newrole = 'siswa';
    }

    if ($username === '' || $name === '') {
        flash('error', 'Username dan nama wajib diisi.');
        go('index.php?page=users' . ($id ? '&edit=' . $id : ''));
    }

    try {
        if ($id > 0) {
            $sql = 'UPDATE users SET username = ?, name = ?, role = ?';
            $params = [$username, $name, $newrole];
            if ($newpass !== '') {
                $sql .= ', password = ?';
                $params[] = password_hash($newpass, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id = ?';
            $params[] = $id;
            $db->prepare($sql)->execute($params);
            flash('success', 'Data pengguna diperbarui.');
        } else {
            $password = $newpass !== '' ? $newpass : 'rojul';
            $db->prepare('INSERT INTO users (username, password, name, role) VALUES (?, ?, ?, ?)')
                ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $name, $newrole]);
            flash('success', 'Pengguna baru ditambahkan.');
        }
    } catch (PDOException $ex) {
        flash('error', 'Username sudah dipakai, gunakan username lain.');
        go('index.php?page=users' . ($id ? '&edit=' . $id : ''));
    }
    go('index.php?page=users');
}

if ($action === 'delete_user' && role() === 'sa' && isset($_GET['id'])) {
    if (!csrf_ok($_GET['t'] ?? '')) {
        flash('error', 'Sesi tidak valid, silakan coba lagi.');
        go('index.php?page=users');
    }
    $id = (int) $_GET['id'];
    if ($id !== (int) $_SESSION['user']['id']) {
        $q = $db->prepare('SELECT image FROM projects WHERE user_id = ?');
        $q->execute([$id]);
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $img) {
            delete_image($img);
        }
        $db->prepare('DELETE FROM projects WHERE user_id = ?')->execute([$id]);
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('success', 'Pengguna dan project miliknya dihapus.');
    } else {
        flash('error', 'Kamu tidak bisa menghapus akunmu sendiri.');
    }
    go('index.php?page=users');
}

/* ============================================================
   DATA HALAMAN
   ============================================================ */
$page = $_GET['page'] ?? 'dashboard';
$allowedPages = ['dashboard', 'projects', 'admin', 'project_form', 'users'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}
if ($page === 'admin' && !is_admin()) {
    $page = 'dashboard';
}
if ($page === 'users' && role() !== 'sa') {
    $page = 'dashboard';
}

if ($page === 'dashboard') {
    $total = (int) $db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    $mineQuery = $db->prepare('SELECT COUNT(*) FROM projects WHERE user_id = ?');
    $mineQuery->execute([$_SESSION['user']['id']]);
    $mine = (int) $mineQuery->fetchColumn();
    $members = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
}

$uid = (int) $_SESSION['user']['id'];

/* Kartu project (dipakai di dashboard & semua project) */
function project_card($p, $showActions = false)
{
    $can = is_admin() || (int) $p['user_id'] === (int) $_SESSION['user']['id'];
    ?>
    <article class="card reveal" data-tilt="10">
        <?php if ($p['image']): ?>
            <img src="uploads/<?= e($p['image']) ?>" alt="Gambar project <?= e($p['title']) ?>" loading="lazy">
        <?php else: ?>
            <div class="no-image"><span>&lt;/&gt;</span></div>
        <?php endif; ?>

        <div class="card-body">
            <span class="tag"><?= e($p['name']) ?></span>
            <h3><?= e($p['title']) ?></h3>
            <p><?= e($p['description']) ?></p>
            <div class="actions">
                <?php if ($p['link']): ?>
                    <a class="btn small" target="_blank" rel="noopener" href="<?= e($p['link']) ?>">Link</a>
                <?php endif; ?>
                <?php if ($p['demo']): ?>
                    <a class="btn small primary" target="_blank" rel="noopener" href="<?= e($p['demo']) ?>">Demo</a>
                <?php endif; ?>
                <?php if ($showActions && $can): ?>
                    <a class="btn small" href="index.php?page=project_form&id=<?= (int) $p['id'] ?>">Edit</a>
                    <a class="btn small danger"
                       onclick="return confirm('Hapus project ini?')"
                       href="index.php?action=delete_project&id=<?= (int) $p['id'] ?>&t=<?= e(csrf()) ?>">Hapus</a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

page_head();
?>
    <header class="topbar">
        <div class="brand">
            <img src="uploads/logo.png" alt="Logo" class="brand-logo" style="width: 32px; height: 32px; object-fit: contain;">
            <div>
                <b>Etalase Digital Skills</b>
                <small>XI RPL • UKK Level 3</small>
            </div>
        </div>
        <div class="user-area">
            <span class="user-chip">
                <i class="dot"></i>
                <?= e($_SESSION['user']['name']) ?>
                <em><?= e(strtoupper($_SESSION['user']['role'])) ?></em>
            </span>
            <a class="btn ghost" href="index.php?logout=1">Keluar</a>
        </div>
    </header>

    <div class="layout">
        <aside class="sidebar">
            <a class="<?= ($page === 'dashboard' ? 'active' : '') ?>" href="index.php">🏠 <span>Dashboard</span></a>
            <a class="<?= ($page === 'projects' ? 'active' : '') ?>" href="index.php?page=projects">📁 <span>Semua Project</span></a>
            <a class="<?= ($page === 'project_form' ? 'active' : '') ?>" href="index.php?page=project_form">➕ <span>Tambah Project</span></a>
            <?php if (is_admin()): ?>
                <a class="<?= ($page === 'admin' ? 'active' : '') ?>" href="index.php?page=admin">🛠 <span>Kelola Project</span></a>
            <?php endif; ?>
            <?php if (role() === 'sa'): ?>
                <a class="<?= ($page === 'users' ? 'active' : '') ?>" href="index.php?page=users">👥 <span>Data Siswa</span></a>
            <?php endif; ?>
        </aside>

        <main class="content">
            <?php flash_box(); ?>

            <?php if ($page === 'dashboard'): ?>
                <section class="hero">
                    <div class="hero-text">
                        <span class="eyebrow">PORTFOLIO SISWA</span>
                        <h1>
                            Etalase<br>
                            <span class="gradient-text" data-typing='["Digital Skills","Karya Kreatif","Project Terbaik","Masa Depan RPL"]'>Digital Skills</span>
                        </h1>
                        <p>Tempat siswa menampilkan karya digital, project, link pendukung, dan demo.</p>
                        <a class="btn primary glow" href="index.php?page=project_form">+ Tambah Project</a>
                    </div>
                    <?= cube_scene() ?>
                </section>

                <div class="stats">
                    <div class="stat reveal" data-tilt="12">
                        <span>Total Project</span>
                        <b data-count="<?= $total ?>"><?= $total ?></b>
                    </div>
                    <div class="stat reveal" data-tilt="12">
                        <span>Project Saya</span>
                        <b data-count="<?= $mine ?>"><?= $mine ?></b>
                    </div>
                    <div class="stat reveal" data-tilt="12">
                        <span>Anggota</span>
                        <b data-count="<?= $members ?>"><?= $members ?></b>
                    </div>
                </div>

                <h2 class="section-title">Project Terbaru</h2>
                <?php
                $ps = $db->query(
                    'SELECT p.*, u.name FROM projects p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC LIMIT 6'
                )->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (!$ps): ?>
                    <div class="empty reveal">Belum ada project. Jadilah yang pertama menambahkan karya! ✨</div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($ps as $p) { project_card($p); } ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page === 'projects'): ?>
                <div class="page-head">
                    <div>
                        <h1>Semua Project</h1>
                        <p>Galeri karya yang sudah ditambahkan ke etalase.</p>
                    </div>
                    <a class="btn primary" href="index.php?page=project_form">+ Project Baru</a>
                </div>
                <?php
                $ps = $db->query(
                    'SELECT p.*, u.name FROM projects p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC'
                )->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <?php if (!$ps): ?>
                    <div class="empty reveal">Belum ada project.</div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach ($ps as $p) { project_card($p, true); } ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page === 'admin'): ?>
                <div class="page-head">
                    <div>
                        <h1>Kelola Project</h1>
                        <p>Tabel manajemen seluruh project.</p>
                    </div>
                    <a class="btn primary" href="index.php?page=project_form">+ Project Baru</a>
                </div>

                <div class="table-wrap reveal">
                    <table>
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Pemilik</th>
                                <th>Link</th>
                                <th>Demo</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $ps = $db->query(
                                'SELECT p.*, u.name FROM projects p JOIN users u ON u.id = p.user_id ORDER BY p.id DESC'
                            )->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($ps as $p):
                            ?>
                                <tr>
                                    <td>
                                        <b><?= e($p['title']) ?></b>
                                        <small><?= e($p['description']) ?></small>
                                    </td>
                                    <td><?= e($p['name']) ?></td>
                                    <td><?= $p['link'] ? '<a target="_blank" rel="noopener" href="' . e($p['link']) . '">Buka</a>' : '-' ?></td>
                                    <td><?= $p['demo'] ? '<a target="_blank" rel="noopener" href="' . e($p['demo']) . '">Buka</a>' : '-' ?></td>
                                    <td class="nowrap">
                                        <a class="btn small" href="index.php?page=project_form&id=<?= (int) $p['id'] ?>">Edit</a>
                                        <a class="btn small danger"
                                           onclick="return confirm('Hapus project ini?')"
                                           href="index.php?action=delete_project&id=<?= (int) $p['id'] ?>&t=<?= e(csrf()) ?>">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$ps): ?>
                                <tr><td colspan="5" class="center">Belum ada project.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page === 'project_form'):
                $id = (int) ($_GET['id'] ?? 0);
                $p = ['title' => '', 'description' => '', 'image' => '', 'link' => '', 'demo' => ''];
                if ($id) {
                    $q = $db->prepare('SELECT * FROM projects WHERE id = ?');
                    $q->execute([$id]);
                    $found = $q->fetch(PDO::FETCH_ASSOC);
                    if (!$found || (!is_admin() && (int) $found['user_id'] !== $uid)) {
                        die('Akses ditolak.');
                    }
                    $p = $found;
                }
            ?>
                <div class="form-card reveal" data-tilt="3">
                    <h1><?= $id ? 'Edit Project' : 'Tambah Project' ?></h1>
                    <p>Isi data karya digital kamu.</p>
                    <form method="post" action="index.php?action=save_project" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <label>
                            Judul Project
                            <input name="title" required value="<?= e($p['title']) ?>" placeholder="Contoh: Website Portfolio">
                        </label>
                        <label>
                            Deskripsi
                            <textarea name="description" required placeholder="Jelaskan project secara singkat..."><?= e($p['description']) ?></textarea>
                        </label>
                        <label>
                            Gambar Project
                            <input type="file" name="image" accept="image/*" id="image-input">
                        </label>
                        <?php if ($p['image']): ?>
                            <img class="preview" id="image-preview" src="uploads/<?= e($p['image']) ?>" alt="Preview">
                        <?php else: ?>
                            <img class="preview" id="image-preview" alt="Preview" hidden>
                        <?php endif; ?>
                        <div class="two-col">
                            <label>
                                Link Project
                                <input name="link" value="<?= e($p['link']) ?>" placeholder="https://github.com/...">
                            </label>
                            <label>
                                Link Demo
                                <input name="demo" value="<?= e($p['demo']) ?>" placeholder="https://demo-kamu.com">
                            </label>
                        </div>
                        <div class="form-actions">
                            <button class="btn primary" type="submit">Simpan Project</button>
                            <a class="btn ghost" href="index.php?page=projects">Batal</a>
                        </div>
                    </form>
                </div>

            <?php elseif ($page === 'users'):
                $editId = (int) ($_GET['edit'] ?? 0);
                $eu = ['id' => 0, 'username' => '', 'name' => '', 'role' => 'siswa'];
                if ($editId) {
                    $q = $db->prepare('SELECT id, username, name, role FROM users WHERE id = ?');
                    $q->execute([$editId]);
                    $found = $q->fetch(PDO::FETCH_ASSOC);
                    if ($found) {
                        $eu = $found;
                    }
                }
                $users = $db->query('SELECT * FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="page-head">
                    <div>
                        <h1>Data Siswa</h1>
                        <p>Kelola akun pengguna etalase (khusus SA).</p>
                    </div>
                </div>

                <div class="split">
                    <div class="table-wrap reveal">
                        <table>
                            <thead>
                                <tr><th>Nama</th><th>Username</th><th>Role</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><b><?= e($u['name']) ?></b></td>
                                        <td><?= e($u['username']) ?></td>
                                        <td><span class="tag"><?= e(strtoupper($u['role'])) ?></span></td>
                                        <td class="nowrap">
                                            <a class="btn small" href="index.php?page=users&edit=<?= (int) $u['id'] ?>">Edit</a>
                                            <?php if ((int) $u['id'] !== $uid): ?>
                                                <a class="btn small danger"
                                                   onclick="return confirm('Hapus pengguna ini beserta semua project miliknya?')"
                                                   href="index.php?action=delete_user&id=<?= (int) $u['id'] ?>&t=<?= e(csrf()) ?>">Hapus</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-card reveal" data-tilt="4">
                        <h2><?= $eu['id'] ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h2>
                        <form method="post" action="index.php?action=save_user">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $eu['id'] ?>">
                            <label>
                                Nama Lengkap
                                <input name="name" required value="<?= e($eu['name']) ?>" placeholder="Nama siswa">
                            </label>
                            <label>
                                Username
                                <input name="username" required value="<?= e($eu['username']) ?>" placeholder="username">
                            </label>
                            <label>
                                Password <?= $eu['id'] ? '(kosongkan jika tidak diganti)' : '(default: rojul)' ?>
                                <input name="password" type="password" placeholder="••••••">
                            </label>
                            <label>
                                Role
                                <select name="role">
                                    <?php foreach (['siswa', 'admin', 'sa'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $eu['role'] === $r ? 'selected' : '' ?>><?= strtoupper($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <div class="form-actions">
                                <button class="btn primary" type="submit">Simpan</button>
                                <?php if ($eu['id']): ?>
                                    <a class="btn ghost" href="index.php?page=users">Batal</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
<?php page_foot(); ?>