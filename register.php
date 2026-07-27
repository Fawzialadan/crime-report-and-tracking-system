<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $password === '' || $confirmPassword === '') {
        flash('error', 'Please complete all required registration fields.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect('register.php');
    }

    if (!preg_match('/^\d{9}$/', $phone)) {
        flash('error', 'Phone number must be exactly 9 digits (e.g. 907118345).');
        redirect('register.php');
    }

    if ($password !== $confirmPassword) {
        flash('error', 'Passwords do not match.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters long.');
        redirect('register.php');
    }

    if (User::findByEmail($email)) {
        flash('error', 'That email is already registered.');
        redirect('register.php');
    }

    $userId = User::registerCitizen([
        'full_name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'password' => $password,
    ]);

    $user = User::find($userId);
    if ($user) {
        login_user($user);
    }

    flash('success', 'Registration successful. You can now submit reports.');
    redirect('index.php');
}

$pageTitle = 'Register';
$authPage = true;
require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --bg-main: #0a0f1c;
        --card-bg: rgba(30, 41, 59, 0.7);
        --accent-primary: #6366f1;
        --accent-glow: rgba(99, 102, 241, 0.5);
        --text-white: #f8fafc;
        --text-dim: #94a3b8;
    }

    body {
        background: radial-gradient(circle at top right, #1e293b, #0a0f1c) !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Inter', sans-serif;
        padding: 20px;
    }

    .auth-layout {
        display: flex;
        width: 100%;
        max-width: 1100px;
        background: var(--card-bg);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.8s ease-out;
    }

    /* Left Side */
    .auth-side {
        flex: 1;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(10, 15, 28, 0.8));
        padding: 60px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    .auth-brand-badge {
        width: 60px;
        height: 60px;
        background: var(--accent-primary);
        color: white;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 1.5rem;
        box-shadow: 0 0 20px var(--accent-glow);
        margin-bottom: 30px;
    }

    .auth-side h2 {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--text-white);
        margin-bottom: 10px;
    }

    .auth-brand-subtitle {
        color: var(--accent-primary);
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 0.8rem;
        margin-bottom: 25px;
    }

    /* Right Side Form */
    .auth-card {
        flex: 1.2;
        padding: 50px 60px;
        background: rgba(15, 23, 42, 0.4);
    }

    .section-label {
        color: var(--accent-primary);
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    .auth-card h2 {
        color: var(--text-white);
        font-weight: 700;
        margin-bottom: 10px;
    }

    .auth-text {
        color: var(--text-dim);
        font-size: 0.9rem;
        margin-bottom: 30px;
    }

    /* Form Grid */
    .auth-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .full-width { grid-column: span 2; }

    .auth-form label {
        display: block;
        color: var(--text-white);
        font-size: 0.8rem;
        margin-bottom: 8px;
        font-weight: 500;
    }

    .auth-form input {
        width: 100%;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 12px 16px;
        border-radius: 12px;
        color: white;
        transition: 0.3s;
    }

    .auth-form input:focus {
        outline: none;
        border-color: var(--accent-primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        background: rgba(0, 0, 0, 0.3);
    }

    .button-primary {
        width: 100%;
        background: var(--accent-primary);
        color: white;
        border: none;
        padding: 14px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 10px;
    }

    .button-primary:hover {
        background: #4f46e5;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px var(--accent-glow);
    }

    .auth-help {
        margin-top: 25px;
        text-align: center;
        font-size: 0.85rem;
    }

    .auth-help span { color: var(--text-dim); }
    .auth-help a {
        color: var(--accent-primary);
        text-decoration: none;
        font-weight: 600;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 900px) {
        .auth-layout { flex-direction: column; }
        .auth-form-grid { grid-template-columns: 1fr; }
        .full-width { grid-column: span 1; }
        .auth-side, .auth-card { padding: 40px; }
    }
</style>

<section class="auth-layout">
    <aside class="auth-side">
        <div class="auth-brand-badge">SC</div>
        <div>
            <h2>Citizen Portal</h2>
            <p class="auth-brand-subtitle">Join Sentinel Case Tracking</p>
        </div>
        <p class="auth-text" style="color: rgba(255,255,255,0.7)">
            Is diiwaangeli si aad u soo gudbiso dambiyada, ula socoto baaritaanada, una hesho cadaalad degdeg ah.
        </p>
    </aside>

    <article class="auth-card">
        <p class="section-label">New Registration</p>
        <h2>Create Account</h2>
        <p class="auth-text">Buuxi foomka hoose si aad qayb uga noqoto nidaamka.</p>

        <form method="POST" class="auth-form">
            <div class="auth-form-grid">
                <div class="full-width">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" type="text" placeholder="Magacaaga oo buuxa" value="<?= e(old('full_name')) ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" placeholder="tusaale@email.com" value="<?= e(old('email')) ?>" required>
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" inputmode="numeric" pattern="\d{9}" maxlength="9" placeholder="xxxxxxxxx" title="Enter exactly 9 digits, e.g. 907118345" value="<?= e(old('phone')) ?>" required>
                    <small style="display:block; margin-top:6px; color: var(--text-dim);"></small>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" placeholder="••••••••" required>
                </div>
                <div>
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" placeholder="••••••••" required>
                </div>
            </div>
            <button class="button-primary" type="submit">Create Citizen Account</button>
        </form>

        <div class="auth-help">
            <span>Horey ma isu diiwaangelisay?</span>
            <a href="login.php">Login here</a>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>