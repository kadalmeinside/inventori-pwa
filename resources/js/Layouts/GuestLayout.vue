<template>
    <!-- Full-page liquid glass auth shell -->
    <div class="guest-layout">
        <!-- Decorative blobs -->
        <div class="blob blob--1" />
        <div class="blob blob--2" />
        <div class="blob blob--3" />

        <!-- Content card -->
        <div class="guest-card">
            <!-- Top logo area -->
            <div class="guest-card__logo">
                <div class="guest-card__logo-icon">
                    <img src="/logo-inventory.png" alt="Inventori" class="guest-logo-img">
                </div>
                <h1 class="guest-card__app-name">Inventori</h1>
                <p class="guest-card__tagline">Inventory Management System</p>
            </div>

            <!-- Slot -->
            <div class="guest-card__body">
                <slot />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ─── Full-page container ────────────────────────────────────────────────── */
.guest-layout {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    background: #f2f4f8;
}

/* ─── Animated blobs ─────────────────────────────────────────────────────── */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.55;
    animation: blobFloat 10s ease-in-out infinite alternate;
    pointer-events: none;
}

.blob--1 {
    width: 500px;
    height: 500px;
    top: -150px;
    left: -150px;
    background: radial-gradient(circle, rgba(0, 122, 255, 0.15), transparent 70%);
    animation-delay: 0s;
}

.blob--2 {
    width: 400px;
    height: 400px;
    bottom: -100px;
    right: -100px;
    background: radial-gradient(circle, rgba(175, 82, 222, 0.12), transparent 70%);
    animation-delay: -3s;
}

.blob--3 {
    width: 300px;
    height: 300px;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: radial-gradient(circle, rgba(48, 176, 199, 0.08), transparent 70%);
    animation-delay: -6s;
}

@keyframes blobFloat {
    from { transform: translate(0, 0) scale(1); }
    to   { transform: translate(30px, -20px) scale(1.08); }
}

.blob--3 {
    animation-name: blobFloat3;
}
@keyframes blobFloat3 {
    from { transform: translate(-50%, -50%) scale(1); }
    to   { transform: translate(calc(-50% + 20px), calc(-50% + 15px)) scale(1.05); }
}

/* ─── Card ───────────────────────────────────────────────────────────────── */
.guest-card {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(40px) saturate(200%);
    -webkit-backdrop-filter: blur(40px) saturate(200%);
    border: 1px solid rgba(255, 255, 255, 0.90);
    border-radius: 2rem;
    box-shadow:
        0 32px 80px rgba(0, 80, 200, 0.18),
        0 8px 24px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 1),
        inset 0 -1px 0 rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

/* ─── Logo area ──────────────────────────────────────────────────────────── */
.guest-card__logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2.25rem 2rem 1.5rem;
    background: linear-gradient(160deg, rgba(0, 122, 255, 0.06) 0%, transparent 100%);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    gap: 0.5rem;
}

.guest-card__logo-icon {
    width: 4.5rem;
    height: 4.5rem;
    background: white;
    border-radius: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow:
        0 8px 28px rgba(0, 122, 255, 0.22),
        0 2px 8px rgba(0,0,0,0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    margin-bottom: 0.25rem;
    overflow: hidden;
    padding: 8px;
}

.guest-logo-img { width: 100%; height: 100%; object-fit: contain; border-radius: 0.625rem; }

.guest-card__app-name {
    font-size: 1.625rem;
    font-weight: 800;
    letter-spacing: -0.04em;
    background: linear-gradient(135deg, #007AFF, #5856D6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0;
    line-height: 1;
}

.guest-card__tagline {
    font-size: 0.78rem;
    color: rgba(0, 0, 0, 0.38);
    font-weight: 500;
    margin: 0;
    letter-spacing: 0.01em;
}

/* ─── Body ───────────────────────────────────────────────────────────────── */
.guest-card__body {
    padding: 1.75rem 1.75rem 2rem;
}
</style>
