<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_login(); // Waxaan u tagnay si loo hubiyo in qofka soo galay uu yahay user jira

$user = current_user();
$pageTitle = 'Welcome to CrimeCore';
$dashboardPath = default_dashboard_for_role($user['role']);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* Design-ka Xayaysiinta (Marketing Style) */
    :root {
        --accent: #ec4899;
        --accent-glow: rgba(236, 72, 153, 0.25);
        --glass: rgba(17, 24, 39, 0.8);
    }

    .promo-wrapper {
        min-height: 85vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 0 5%;
        background: radial-gradient(circle at center, var(--accent-glow), transparent 600px);
        text-align: center;
    }

    /* Hero Headline */
    .promo-hero {
        max-width: 900px;
        animation: slideUp 1s ease-out;
    }

    .promo-hero span.badge-top {
        color: var(--accent);
        font-weight: 800;
        letter-spacing: 5px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 20px;
    }

    .promo-hero h1 {
        font-size: clamp(2.5rem, 8vw, 5rem);
        font-weight: 800;
        line-height: 1;
        margin-bottom: 25px;
        color: #fff;
    }

    .promo-hero h1 span {
        background: linear-gradient(to right, #fff, var(--accent));
        /* -webkit-background-clip: text; */
        -webkit-text-fill-color: transparent;
    }

    .promo-hero p.lead {
        font-size: 1.4rem;
        color: #9ca3af;
        margin-bottom: 45px;
        line-height: 1.6;
    }

    /* Marketing Features Section */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        width: 100%;
        max-width: 1100px;
        margin-top: 50px;
    }

    .feature-card {
        background: var(--glass);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 40px;
        border-radius: 30px;
        transition: 0.4s;
    }

    .feature-card:hover {
        border-color: var(--accent);
        transform: translateY(-10px);
    }

    .feature-icon {
        font-size: 2.5rem;
        margin-bottom: 20px;
        display: block;
    }

    .feature-card h3 {
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #fff;
    }

    .feature-card p {
        color: #9ca3af;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    /* Buttons */
    .btn-group {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-main {
        padding: 20px 50px;
        border-radius: 100px;
        font-weight: 800;
        font-size: 1.1rem;
        text-decoration: none;
        transition: 0.3s;
    }

    .btn-primary-glow {
        background: var(--accent);
        color: white;
        box-shadow: 0 10px 30px var(--accent-glow);
    }

    .btn-outline {
        border: 2px solid rgba(255, 255, 255, 0.1);
        color: white;
    }

    .btn-main:hover {
        transform: scale(1.05);
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(40px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="promo-wrapper">
    <section class="promo-hero">
        <span class="badge-top">Secure Community Platform</span>
        <h1>Welcome to the <span>Future</span> of Safety.</h1>
        <p class="lead">
            CrimeCore waa nidaamka ugu horreeya ee maareynta amniga, kaas oo isku xira muwaadiniinta iyo hay'adaha fulinta sharciga si loo helo bulsho ammaan ah.
        </p>
        
        <div class="btn-group">
            <a href="<?= e($dashboardPath) ?>" class="btn-main btn-primary-glow">
                Aad Dashboard-kaaga
            </a>
            <a href="submit_report.php" class="btn-main btn-outline">
                Gudbi Dacwad Cusub
            </a>
        </div>
    </section>

    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-icon">🚀</span>
            <h3>Dhowrsanaan</h3>
            <p>Dhib kasta oo aad soo sheegto waxaa loo maareeyaa si degdeg ah oo hufan, adigoo helaya warbixin dhamaystiran.</p>
        </div>

        <div class="feature-card">
            <span class="feature-icon">🛡️</span>
            <h3>Amni Buuxa</h3>
            <p>Xogtaada waa mid qarsoodi ah oo loo ilaaliyo si heer sare ah, iyadoo la isticmaalayo teknoolajiyadii ugu dambaysay.</p>
        </div>

        <div class="feature-card">
            <span class="feature-icon">📱</span>
            <h3>Dabagalka Kiiska</h3>
            <p>Kama welwelaysid halka ay wax marayaan. Si toos ah ayaad uga dhex arki kartaa system-ka isbedel kasta.</p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>