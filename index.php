<?php
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#0b0b0d">
  <meta name="description" content="Cora — دستیار هوش مصنوعی برای گفتگو، کدنویسی و ساخت تصویر">
  <title>Cora</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/cora-logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/app.css?v=4">
</head>
<body>
<div class="shell" id="shell">
  <aside class="sidebar" id="sidebar">
    <div class="side-top">
      <button class="brand" id="homeButton" type="button">
        <img src="assets/cora-logo.svg" alt="" class="brand-logo">
        <span class="brand-copy"><b>Cora</b><small>Intelligence</small></span>
      </button>
      <button class="icon-button mobile-only" id="sidebarClose" type="button" aria-label="بستن"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
    </div>

    <button class="new-chat" id="newChat" type="button">
      <span class="new-chat-icon"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg></span>
      <span>گفتگوی جدید</span>
      <kbd>Ctrl K</kbd>
    </button>

    <label class="history-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>
      <input id="historySearch" type="search" placeholder="جستجوی گفتگو..." autocomplete="off">
    </label>

    <div class="side-label"><span>گفتگوها</span><button id="refreshHistory" type="button" aria-label="تازه‌سازی"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2.2 5.5"/><path d="M20 4v7h-7"/></svg></button></div>
    <div class="history-list" id="historyList"><div class="history-placeholder"><i></i><i></i><i></i></div></div>

    <div class="side-footer">
      <div class="quota" id="quotaCard">
        <div class="quota-head"><span>سهمیه امروز</span><strong id="quotaText">—</strong></div>
        <div class="quota-track"><i id="quotaBar"></i></div>
      </div>
      <button class="account" id="accountButton" type="button">
        <span class="account-avatar" id="accountAvatar">C</span>
        <span><b id="accountName">حساب Cora</b><small id="accountEmail">ورود برای ذخیره تاریخچه</small></span>
        <svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
      </button>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <main class="main">
    <header class="topbar">
      <div class="topbar-start">
        <button class="icon-button menu-button" id="menuButton" type="button" aria-label="منو"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        <button class="model-trigger" id="modelTrigger" type="button">
          <img id="activeModelAvatar" src="assets/cora-logo.svg" alt="">
          <span><b id="activeModelName">Cora</b><small id="activeModelHint">مدل هوشمند</small></span>
          <svg viewBox="0 0 24 24"><path d="m9 10 3 3 3-3"/></svg>
        </button>
      </div>
      <div class="topbar-actions">
        <button class="image-launch" id="imageLaunch" type="button"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg><span>ساخت تصویر</span></button>
        <button class="icon-button" id="themeToggle" type="button" aria-label="تغییر پوسته"><svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg></button>
        <button class="login-button" id="loginButton" type="button">ورود</button>
      </div>
    </header>

    <section class="conversation" id="conversation">
      <div class="welcome" id="welcome">
        <div class="hero-mark"><img src="assets/cora-logo.svg" alt="Cora"><span></span></div>
        <h1>چی می‌خوای بسازیم؟</h1>
        <p>سؤال بپرس، کد بنویس، تحلیل کن یا ایده‌ات را به تصویر تبدیل کن.</p>
        <div class="starter-grid">
          <button class="starter" data-prompt="یک API امن با PHP و MySQL طراحی کن و ساختارش را مرحله‌به‌مرحله توضیح بده"><span>⌁</span><b>کدنویسی</b><small>طراحی، دیباگ و معماری</small></button>
          <button class="starter" data-prompt="این موضوع را عمیق تحلیل کن و نکات اصلی و ریسک‌ها را جدا بنویس"><span>◌</span><b>تحلیل</b><small>جمع‌بندی دقیق و ساختاریافته</small></button>
          <button class="starter" data-image="1" data-prompt="یک شهر آینده‌نگر ایرانی در شب با معماری مدرن، جزئیات سینمایی و ترکیب‌بندی حرفه‌ای"><span>✦</span><b>تصویر</b><small>ایده را به تصویر تبدیل کن</small></button>
        </div>
      </div>
      <div class="messages" id="messages" aria-live="polite"></div>
      <div class="bottom-space"></div>
    </section>

    <div class="composer-dock">
      <form class="composer" id="composer">
        <textarea id="prompt" rows="1" maxlength="16000" placeholder="پیامت را بنویس..." aria-label="پیام"></textarea>
        <div class="composer-bottom">
          <div class="composer-status"><span class="live-dot"></span><span id="composerStatus">Cora آماده است</span></div>
          <div class="composer-actions">
            <button class="mini-image" id="miniImage" type="button" title="ساخت تصویر"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg></button>
            <button class="send" id="sendButton" type="submit" aria-label="ارسال"><svg class="send-arrow" viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></svg><svg class="send-stop" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg></button>
          </div>
        </div>
      </form>
      <div class="fine-print">Cora ممکن است اشتباه کند؛ اطلاعات مهم را بررسی کن.</div>
    </div>
  </main>
</div>

<div class="modal" id="modelModal" role="dialog" aria-modal="true">
  <div class="model-panel panel">
    <div class="panel-head"><div><b>انتخاب مدل</b><small>مدل مناسب کارت را انتخاب کن</small></div><button class="close-button" data-close="modelModal">×</button></div>
    <div class="model-list" id="modelList"></div>
  </div>
</div>

<div class="modal" id="imageStudio" role="dialog" aria-modal="true">
  <div class="image-panel panel">
    <div class="panel-head"><div><b>تصویر با Cora</b><small>مختصر توضیح بده؛ جزئیات را Cora کامل می‌کند</small></div><button class="close-button" data-close="imageStudio">×</button></div>
    <div class="image-stage" id="imageStage">
      <canvas id="neuralCanvas" width="720" height="720"></canvas>
      <div class="image-idle" id="imageIdle"><img src="assets/cora-logo.svg" alt=""><span>تصویر اینجا ساخته می‌شود</span></div>
      <div class="image-progress" id="imageProgress"><b>در حال شکل‌گیری</b><small id="imageProgressText">اتصال نقاط...</small><div class="progress-dots"><i></i><i></i><i></i><i></i></div></div>
      <img id="generatedImage" alt="تصویر ساخته‌شده توسط Cora">
      <span class="copyright-chip">© CORA AI</span>
    </div>
    <textarea id="imagePrompt" rows="3" maxlength="6000" placeholder="مثلاً: یک خودرو مفهومی مشکی در خیابان بارانی تهران، نور سینمایی..."></textarea>
    <div class="image-controls">
      <select id="imageSize"><option value="1024x1024">مربع 1:1</option><option value="768x1024">عمودی 3:4</option><option value="1024x768">افقی 4:3</option></select>
      <button class="generate-button" id="generateImage" type="button"><span>ساخت تصویر</span><svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
    </div>
  </div>
</div>

<div class="modal" id="authModal" role="dialog" aria-modal="true">
  <div class="auth-panel panel">
    <button class="close-button auth-close" data-close="authModal">×</button>
    <img src="assets/cora-logo.svg" alt="Cora" class="auth-logo">
    <h2>به Cora خوش اومدی</h2>
    <p>تاریخچه، مدل انتخابی و تصاویرت را نگه دار.</p>
    <div class="auth-tabs"><button class="active" data-auth-tab="login">ورود</button><button data-auth-tab="register">ثبت‌نام</button></div>
    <div class="auth-error" id="authError"></div>
    <form id="loginForm" class="auth-form">
      <label>ایمیل<input name="email" type="email" required autocomplete="email"></label>
      <label>رمز عبور<input name="password" type="password" required autocomplete="current-password"></label>
      <button type="submit">ورود به Cora</button>
    </form>
    <form id="registerForm" class="auth-form hidden">
      <label>نام<input name="name" type="text" minlength="2" maxlength="80" required autocomplete="name"></label>
      <label>ایمیل<input name="email" type="email" required autocomplete="email"></label>
      <label>رمز عبور<input name="password" type="password" minlength="10" required autocomplete="new-password"><small>حداقل ۱۰ کاراکتر، شامل حرف و عدد</small></label>
      <button type="submit">ساخت حساب</button>
    </form>
  </div>
</div>

<div class="toast-zone" id="toastZone"></div>
<script src="assets/app.js?v=4" defer></script>
</body>
</html>
