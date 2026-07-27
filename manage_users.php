<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_role('admin');

$filters = [
    'role' => trim($_GET['role'] ?? ''),
    'keyword' => trim($_GET['keyword'] ?? ''),
];

// ... (Koodhkaaga POST Logic-ga ah sidiisii ha u ahaado)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_user') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'citizen');
    $badgeNumber = trim($_POST['badge_number'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '' || !in_array($role, ['admin', 'officer', 'citizen'], true)) {
        flash('error', 'Please complete the required user fields.');
        redirect('manage_users.php');
    }

    if (User::findByEmail($email)) {
        flash('error', 'That email already exists.');
        redirect('manage_users.php');
    }

    User::createByAdmin([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
        'badge_number' => $role === 'officer' ? $badgeNumber : null,
        'password' => $password,
    ]);

    flash('success', 'User created successfully.');
    redirect('manage_users.php?role=' . rawurlencode($role));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_user') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'citizen');
    $badgeNumber = trim($_POST['badge_number'] ?? '');

    $existing = User::findByEmail($email);
    if ($existing && (int) $existing['id'] !== $userId) {
        flash('error', 'That email is already being used.');
        redirect('manage_users.php');
    }

    User::updateManagedUser($userId, $fullName, $email, $phone, $role, $role === 'officer' ? $badgeNumber : null);
    flash('success', 'User updated successfully.');
    redirect('manage_users.php?role=' . rawurlencode($role));
}

$pageTitle = 'Manage Users';
$users = User::all($filters);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --admin-dark: #0f172a;
        --admin-accent: #6366f1;
        --soft-border: #e2e8f0;
    }

    .admin-header {
        background: var(--admin-dark);
        color: white;
        padding: 35px;
        border-radius: 20px;
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .admin-nav-btn {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        padding: 10px 18px;
        border-radius: 10px;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-weight: 600;
        transition: 0.3s;
    }

    .admin-nav-btn:hover {
        background: var(--admin-accent);
        color: white;
    }

    .user-card {
        background: white;
        border: 1px solid var(--soft-border);
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        transition: 0.3s;
    }

    .user-card:hover {
        border-color: var(--admin-accent);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .form-section {
        background: white;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 40px;
        border: 1px solid var(--soft-border);
    }

    .input-field {
        width: 100%;
        padding: 12px;
        border: 1px solid var(--soft-border);
        border-radius: 10px;
        margin-top: 5px;
        font-size: 0.95rem;
    }

    .role-badge {
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .role-admin {
        background: #fee2e2;
        color: #dc2626;
    }

    .role-officer {
        background: #e0e7ff;
        color: #4338ca;
    }

    .role-citizen {
        background: #f0fdf4;
        color: #16a34a;
    }
</style>

<section class="admin-header">
    <div>
        <span style="color: var(--admin-accent); font-weight: 700; text-transform: uppercase; font-size: 0.8rem;">Admin Control</span>
        <h2 style="font-size: 2rem; margin: 5px 0;">Manage Users</h2>
        <p style="opacity: 0.8; margin: 0;">Add new staff members or update existing user profiles.</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="admin_dashboard.php" class="admin-nav-btn">📊 Dashboard</a>
        <a href="index.php" class="admin-nav-btn" style="background: var(--admin-accent);">🏠 Home</a>
    </div>
</section>

<div class="content-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">

    <aside class="form-section">
        <h3 style="margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">Add New User</h3>
        <form method="POST" class="stack-list">
            <input type="hidden" name="action" value="create_user">

            <div style="margin-bottom: 15px;">
                <label>Full Name</label>
                <input name="full_name" type="text" class="input-field" placeholder="Full name..." required>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Email Address</label>
                <input name="email" type="email" class="input-field" placeholder="email@example.com" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label>User Role</label>
                <select name="role" class="input-field" required>
                    <option value="citizen">Citizen</option>
                    <option value="officer">Officer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <div style="margin-bottom: 15px;">
                <label>Badge Number (Officers Only)</label>
                <input name="badge_number" type="text" class="input-field" placeholder="Optional for others">
            </div>

            <div style="margin-bottom: 20px;">
                <label>Password</label>
                <input name="password" type="text" class="input-field" required>
            </div>

            <button class="button-primary" type="submit" style="width: 100%; padding: 14px; border-radius: 12px; background: var(--admin-accent);">Create Account</button>
        </form>
    </aside>

    <main>
        <div style="background: white; padding: 20px; border-radius: 15px; margin-bottom: 20px; border: 1px solid var(--soft-border);">
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <div style="flex: 2;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Search Users</label>
                    <input name="keyword" type="text" class="input-field" value="<?= e($filters['keyword']) ?>" placeholder="Name or Email...">
                </div>
                <div style="flex: 1;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Filter Role</label>
                    <select name="role" class="input-field">
                        <option value="">All Roles</option>
                        <option value="citizen" <?= $filters['role'] === 'citizen' ? 'selected' : '' ?>>Citizen</option>
                        <option value="officer" <?= $filters['role'] === 'officer' ? 'selected' : '' ?>>Officer</option>
                        <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <button class="button-primary" type="submit" style="padding: 12px 25px; border-radius: 10px;">Filter</button>
            </form>
        </div>

        <div class="user-stack">
            <?php if (!$users): ?>
                <div style="text-align: center; padding: 50px; background: white; border-radius: 20px; border: 1px dashed #cbd5e1;">
                    <p style="color: #94a3b8;">No users found matching your criteria.</p>
                </div>
            <?php endif; ?>

            <?php foreach ($users as $managedUser): ?>
                <form method="POST" class="user-card">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= e((string) $managedUser['id']) ?>">

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span class="role-badge role-<?= $managedUser['role'] ?>"><?= e($managedUser['role']) ?></span>
                        <small style="color: #94a3b8;">User ID: #<?= e((string) $managedUser['id']) ?></small>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div>
                            <label style="font-size: 0.8rem; color: #64748b;">Full Name</label>
                            <input name="full_name" type="text" class="input-field" value="<?= e($managedUser['full_name']) ?>" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: #64748b;">Email Address</label>
                            <input name="email" type="email" class="input-field" value="<?= e($managedUser['email']) ?>" required>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: #64748b;">Phone</label>
                            <input name="phone" type="text" class="input-field" value="<?= e($managedUser['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 15px; align-items: flex-end;">
                        <div>
                            <label style="font-size: 0.8rem; color: #64748b;">Change Role</label>
                            <select name="role" class="input-field">
                                <option value="citizen" <?= $managedUser['role'] === 'citizen' ? 'selected' : '' ?>>Citizen</option>
                                <option value="officer" <?= $managedUser['role'] === 'officer' ? 'selected' : '' ?>>Officer</option>
                                <option value="admin" <?= $managedUser['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 0.8rem; color: #64748b;">Badge Number</label>
                            <input name="badge_number" type="text" class="input-field" value="<?= e($managedUser['badge_number'] ?? '') ?>">
                        </div>
                        <div style="text-align: right;">
                            <button class="button-secondary" type="submit" style="width: 100%; border-color: var(--admin-accent); color: var(--admin-accent); font-weight: 700;">Update Profile</button>
                        </div>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>