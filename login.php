<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if (is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        flash('error', 'Email and password are required.');
        redirect('login.php');
    }

    $user = User::attemptLogin($email, $password);

    if (!$user) {
        flash('error', 'Invalid login details.');
        redirect('login.php');
    }

    login_user($user);
    flash('success', 'Login successful. Welcome back ' . $user['full_name'] . '.');
    redirect('index.php');
}

$pageTitle = 'Login';
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
    }

    .auth-layout {
        display: flex;
        width: 100%;
        max-width: 1000px;
        background: var(--card-bg);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: fadeIn 0.8s ease-out;
    }

    /* Left Side: Brand Info */
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
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-white);
        margin-bottom: 10px;
    }

    .auth-brand-subtitle {
        color: var(--accent-primary);
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        font-size: 0.85rem;
        margin-bottom: 25px;
    }

    /* Right Side: Login Form */
    .auth-card {
        flex: 1;
        padding: 60px;
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
        font-size: 0.95rem;
        margin-bottom: 40px;
    }

    .auth-form div {
        margin-bottom: 20px;
    }

    .auth-form label {
        display: block;
        color: var(--text-white);
        font-size: 0.85rem;
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
        margin-top: 30px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        font-size: 0.85rem;
    }

    .auth-help span {
        color: var(--text-dim);
    }

    .auth-help a {
        color: var(--accent-primary);
        text-decoration: none;
        font-weight: 600;
        transition: 0.2s;
    }

    .auth-help a:hover {
        color: #818cf8;
        text-decoration: underline;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive */
    @media (max-width: 850px) {
        .auth-layout {
            flex-direction: column;
            margin: 20px;
        }

        .auth-side {
            padding: 40px;
        }

        .auth-card {
            padding: 40px;
        }
    }
</style>

<section class="auth-layout">
    <aside class="auth-side">
        <div class="auth-brand-badge">CS</div>
        <div>
            <h2>Crime Management System</h2>
            <p class="auth-brand-subtitle">Crime Tracking Suite</p>
        </div>
        <p class="auth-text" style="color: rgba(255,255,255,0.7)">
            Nidaam casri ah oo loogu talagalay sugidda amniga, dabagalka dambiyada iyo wada shaqaynta hay'adaha sharciga.
        </p>
    </aside>

    <article class="auth-card">
        <p class="section-label">System Gateway</p>
        <h2>Welcome Back</h2>
        <p class="auth-text">Gali email-kaaga iyo password-kaaga si aad u gashid Dashboard-ka.</p>

        <form method="POST" class="auth-form">
            <div>
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" placeholder="admin@gmail.com" value="<?= e(old('email')) ?>" required>
            </div>
            <div>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="••••••••" required>
            </div>
            <button class="button-primary" type="submit">Gudaha u gal</button>
        </form>

        <div class="auth-help">
            <div>
                <span>Account ma haysatid?</span>
                <a href="register.php">Sameey akoon muwaadin</a>
            </div>
            <a href="forgot_password.php">Ma ilowday Password-ka?</a>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>