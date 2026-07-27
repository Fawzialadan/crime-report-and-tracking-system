<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();

// ... (Koodhkaaga POST Logic-ga ah sidiisii ha u ahaado)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '') {
        flash('error', 'Full name, email, and phone are required.');
        redirect('profile.php');
    }

    if (!preg_match('/^\d{9}$/', $phone)) {
        flash('error', 'Phone number must be exactly 9 digits (e.g. 907118345).');
        redirect('profile.php');
    }

    $existing = User::findByEmail($email);
    if ($existing && (int) $existing['id'] !== (int) $user['id']) {
        flash('error', 'That email is already being used by another account.');
        redirect('profile.php');
    }

    User::updateProfile((int) $user['id'], $fullName, $email, $phone);
    $freshUser = User::find((int) $user['id']);
    if ($freshUser) {
        login_user($freshUser);
        $user = current_user();
    }

    flash('success', 'Profile updated successfully.');
    redirect('profile.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'password') {
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $confirmPassword === '') {
        flash('error', 'Please complete the password fields.');
        redirect('profile.php');
    }

    if ($newPassword !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
        redirect('profile.php');
    }

    if (strlen($newPassword) < 6) {
        flash('error', 'Password must be at least 6 characters.');
        redirect('profile.php');
    }

    User::changePassword((int) $user['id'], $newPassword);
    flash('success', 'Password updated successfully.');
    redirect('profile.php');
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --bg-deep: #0f172a;
        --card-bg: #1e293b;
        --accent-pink: #ec4899;
        --text-muted: #94a3b8;
    }

    .hero-panel {
        background: linear-gradient(135deg, var(--bg-deep) 0%, #312e81 100%);
        padding: 40px;
        border-radius: 24px;
        color: white;
        margin-bottom: 30px;
        border: 1px solid #334155;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    .panel {
        background: var(--card-bg);
        border: 1px solid #334155;
        border-radius: 20px;
        padding: 35px;
        transition: 0.3s ease;
    }
    .panel:hover { border-color: var(--accent-pink); }

    .section-label {
        color: var(--accent-pink);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1.5px;
    }

    .input-group { margin-bottom: 20px; }
    
    label {
        display: block;
        color: #f1f5f9;
        font-size: 0.9rem;
        margin-bottom: 8px;
        font-weight: 500;
    }

    input {
        width: 100%;
        padding: 14px;
        background: #0f172a;
        border: 1px solid #334155;
        border-radius: 12px;
        color: white;
        font-size: 1rem;
        transition: 0.3s;
    }
    input:focus {
        border-color: var(--accent-pink);
        outline: none;
        box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.2);
    }
    input:disabled { opacity: 0.5; cursor: not-allowed; background: #1e293b; }

    .button-primary {
        background: var(--accent-pink);
        color: white;
        border: none;
        padding: 14px 28px;
        border-radius: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: 0.3s;
    }
    .button-primary:hover { transform: translateY(-2px); opacity: 0.9; }

    .button-secondary {
        background: transparent;
        color: white;
        border: 1px solid #475569;
        padding: 14px 28px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
    }
    .button-secondary:hover { border-color: var(--accent-pink); color: var(--accent-pink); }
</style>

<section class="hero-panel">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <p class="section-label">Account Center</p>
            <h2 style="font-size: 2.5rem; margin: 10px 0;">Profile Settings</h2>
            <p style="color: var(--text-muted);">Maamul xogtaada gaarka ah iyo amniga koontadaada.</p>
        </div>
        <div class="hero-actions">
            <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">🏠 Back to Dashboard</a>
        </div>
    </div>
</section>

<section class="content-grid" style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
    <article class="panel">
        <p class="section-label">General Information</p>
        <h2 style="color: white; margin: 10px 0 30px;">Update account details</h2>
        
        <form method="POST" class="stack-list">
            <input type="hidden" name="action" value="profile">
            
            <div class="input-group">
                <label for="full_name">Full Name</label>
                <input id="full_name" name="full_name" type="text" value="<?= e($user['full_name']) ?>" required>
            </div>
            
            <div class="input-group">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" value="<?= e($user['email']) ?>" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="text" inputmode="numeric" pattern="\d{9}" maxlength="9" placeholder="907118345" title="Enter exactly 9 digits, e.g. 907118345" value="<?= e($user['phone'] ?? '') ?>" required>
                    <small style="display:block; margin-top:6px; color: var(--text-muted);"></small>
                </div>
                <?php if ($user['role'] === 'officer'): ?>
                    <div class="input-group">
                        <label for="badge_number">Badge Number (Fixed)</label>
                        <input id="badge_number" type="text" value="<?= e($user['badge_number'] ?? '') ?>" disabled title="Officers cannot change their badge number">
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-row" style="margin-top: 20px; display: flex; gap: 15px;">
                <button class="button-primary" type="submit">💾 Save Changes</button>
                <a class="button-secondary" href="<?= e(back_dashboard_path()) ?>">Cancel</a>
            </div>
        </form>
    </article>

    <article class="panel" style="border-top: 4px solid var(--accent-pink);">
        <p class="section-label">Security</p>
        <h2 style="color: white; margin: 10px 0 30px;">Change Password</h2>
        
        <form method="POST" class="stack-list">
            <input type="hidden" name="action" value="password">
            
            <div class="input-group">
                <label for="new_password">New Password</label>
                <input id="new_password" name="new_password" type="password" placeholder="At least 6 characters" required>
            </div>
            
            <div class="input-group">
                <label for="confirm_password">Confirm New Password</label>
                <input id="confirm_password" name="confirm_password" type="password" placeholder="Repeat new password" required>
            </div>
            
            <button class="button-primary" type="submit" style="width: 100%; margin-top: 20px;">🔐 Update Password</button>
        </form>
        
        <div style="margin-top: 30px; padding: 15px; background: rgba(236, 72, 153, 0.1); border-radius: 10px; border-left: 3px solid var(--accent-pink);">
            <p style="font-size: 0.85rem; color: #f1f5f9; margin: 0;"><strong>Talo:</strong> Hubi in password-kaaga uu yahay mid adag oo aan si fudud lagu qiyaasi karin.</p>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>