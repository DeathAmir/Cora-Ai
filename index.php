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
  <meta name="description" content="Cora AI - دستیار هوش مصنوعی برای گفتگو و ساخت تصویر">
  <title>Cora AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/app.css?v=1">
</head>
<body>
<div class="app" id="app">
  <aside class="sidebar" id="sidebar" aria-label="تاریخچه گفتگوها">
    <div class="brand-row">
      <button class="brand" id="brandHome" type="button" aria-label="Cora AI">
        <span class="brand-mark"><svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 2.7c7.35 0 13.3 5.95 13.3 13.3S23.35 29.3 16 29.3 2.7 23.35 2.7 16 8.65 2.7 16 2.7Z"/><path class="cut" d="M20.8 9.5a8.2 8.2 0 1 0 0 13l-2.25-2.3a5.05 5.05 0 1 1 0-8.4l2.25-2.3Z"/></svg></span>
        <span><b>Cora</b><small>AI Workspace</small></span>
      </button>
      <button class="icon-btn sidebar-close" id="sidebarClose" type="button" aria-label="بستن منو">
        <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
      </button>
    </div>

    <button class="new-chat" id="newChat" type="button">
      <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
      <span>گفتگوی جدید</span>
      <kbd>Ctrl K</kbd>
    </button>

    <div class="side-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input id="historySearch" type="search" autocomplete="off" placeholder="جستجو در گفتگوها...">
    </div>

    <nav class="quick-nav">
      <button type="button" data-mode="chat" class="quick-item active">
        <svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
        <span>گفتگوی هوشمند</span><span class="dot-live"></span>
      </button>
      <button type="button" data-mode="image" class="quick-item">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg>
        <span>ساخت تصویر</span><em>AI</em>
      </button>
    </nav>

    <div class="history-head"><span>گفتگوهای اخیر</span><button id="refreshHistory" class="tiny-btn" aria-label="تازه‌سازی"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2 5.3"/><path d="M20 4v7h-7"/></svg></button></div>
    <div class="history-list" id="historyList">
      <div class="history-skeleton"><i></i><i></i><i></i><i></i></div>
    </div>

    <div class="sidebar-bottom">
      <div class="usage-card">
        <span class="spark"><svg viewBox="0 0 24 24"><path d="m12 2 1.8 5.2L19 9l-5.2 1.8L12 16l-1.8-5.2L5 9l5.2-1.8L12 2Z"/></svg></span>
        <div><b>Cora Intelligence</b><small id="providerLabel">آماده اتصال به مدل</small></div>
        <span class="status-pill">Online</span>
      </div>
      <button class="profile-card" id="profileButton" type="button">
        <span class="avatar" id="sidebarAvatar">C</span>
        <span class="profile-copy"><b id="sidebarName">حساب کاربری</b><small id="sidebarEmail">برای ذخیره تاریخچه وارد شوید</small></span>
        <svg viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></svg>
      </button>
    </div>
  </aside>

  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <main class="main">
    <header class="topbar">
      <div class="top-start">
        <button class="icon-btn menu-btn" id="menuButton" type="button" aria-label="بازکردن منو"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
        <button class="model-chip" id="modelChip" type="button"><span class="model-orb"></span><span><b id="modelName">Cora</b><small id="modelType">هوش مصنوعی چندمنظوره</small></span><svg viewBox="0 0 24 24"><path d="m9 10 3 3 3-3"/></svg></button>
      </div>
      <div class="top-actions">
        <button class="icon-btn" id="themeToggle" type="button" aria-label="تغییر پوسته"><svg class="moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg></button>
        <button class="share-btn" id="clearChat" type="button"><svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4h8v2m3 0-1 15H6L5 6"/></svg><span>پاک‌کردن صفحه</span></button>
        <button class="login-top" id="loginTop" type="button">ورود / ثبت‌نام</button>
      </div>
    </header>

    <section class="chat-scroll" id="chatScroll">
      <div class="welcome" id="welcome">
        <div class="hero-orb"><span></span><span></span><svg viewBox="0 0 32 32"><path d="M16 3c7.2 0 13 5.8 13 13s-5.8 13-13 13S3 23.2 3 16 8.8 3 16 3Z"/><path class="cut" d="M20.8 9.5a8.2 8.2 0 1 0 0 13l-2.25-2.3a5.05 5.05 0 1 1 0-8.4l2.25-2.3Z"/></svg></div>
        <h1><span id="helloWord">سلام</span>، چطور می‌تونم کمکت کنم؟</h1>
        <p>با Cora گفتگو کن، کد بنویس، متن تحلیل کن یا ایده‌ات را به تصویر تبدیل کن.</p>
        <div class="suggestions">
          <button class="suggestion" data-prompt="یک برنامه یادگیری حرفه‌ای برای برنامه‌نویسی PHP طراحی کن"><span class="s-icon violet"><svg viewBox="0 0 24 24"><path d="M4 19h16M6 16l3-4 3 2 5-7 2 2"/></svg></span><span><b>یک مسیر یادگیری بساز</b><small>برنامه‌ریزی مرحله‌به‌مرحله و دقیق</small></span><svg class="arrow" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
          <button class="suggestion" data-prompt="یک صفحه لندینگ مدرن برای یک استارتاپ هوش مصنوعی طراحی کن"><span class="s-icon cyan"><svg viewBox="0 0 24 24"><path d="M3 3h18v18H3zM3 9h18M9 9v12"/></svg></span><span><b>طراحی و کدنویسی</b><small>ایده، معماری و کد تمیز</small></span><svg class="arrow" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
          <button class="suggestion" data-mode="image" data-prompt="یک شهر آینده‌نگر ایرانی در شب، معماری مدرن با الهام از کاشی‌کاری ایرانی، نور سینمایی"><span class="s-icon amber"><svg viewBox="0 0 24 24"><path d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4"/></svg></span><span><b>یک تصویر خلق کن</b><small>از توضیح متنی تا تصویر AI</small></span><svg class="arrow" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
          <button class="suggestion" data-prompt="این موضوع را عمیق تحلیل کن: آینده هوش مصنوعی شخصی در پنج سال آینده"><span class="s-icon green"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span><span><b>تحلیل عمیق</b><small>استدلال ساختاریافته و چندلایه</small></span><svg class="arrow" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
        </div>
      </div>
      <div class="messages" id="messages" aria-live="polite"></div>
      <div class="scroll-anchor" id="scrollAnchor"></div>
    </section>

    <div class="composer-wrap">
      <div class="mode-banner image-mode-banner" id="imageModeBanner">
        <span><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg></span>
        <div><b>حالت ساخت تصویر</b><small>توضیح دقیق‌تری بده تا نتیجه بهتر شود.</small></div>
        <select id="imageSize" aria-label="ابعاد تصویر"><option value="1024x1024">1:1 مربع</option><option value="1024x1536">2:3 عمودی</option><option value="1536x1024">3:2 افقی</option></select>
        <button id="exitImageMode" type="button" aria-label="خروج">×</button>
      </div>
      <form class="composer" id="composer">
        <div class="composer-main">
          <textarea id="prompt" rows="1" maxlength="16000" placeholder="از Cora بپرس..." aria-label="پیام"></textarea>
        </div>
        <div class="composer-tools">
          <div class="tools-start">
            <button class="tool-btn" type="button" id="attachButton" title="پیوست فایل (به‌زودی)"><svg viewBox="0 0 24 24"><path d="m21.4 11.6-8.5 8.5a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 1 1-2.8-2.8l8.5-8.5"/></svg></button>
            <button class="tool-btn image-tool" type="button" id="imageModeButton"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg><span>تصویر</span></button>
            <span class="tool-divider"></span>
            <div class="mode-text"><span class="pulse"></span><span id="modeText">پاسخ هوشمند</span></div>
          </div>
          <button class="send-btn" id="sendButton" type="submit" aria-label="ارسال"><svg class="send-icon" viewBox="0 0 24 24"><path d="M12 19V5M6 11l6-6 6 6"/></svg><svg class="stop-icon" viewBox="0 0 24 24"><rect x="7" y="7" width="10" height="10" rx="2"/></svg></button>
        </div>
      </form>
      <p class="composer-note">Cora ممکن است اشتباه کند؛ اطلاعات مهم را بررسی کنید.</p>
    </div>
  </main>
</div>

<div class="modal-backdrop" id="authModal" role="dialog" aria-modal="true" aria-labelledby="authTitle">
  <div class="auth-card">
    <button class="modal-close" id="authClose" type="button" aria-label="بستن">×</button>
    <div class="auth-logo"><span class="brand-mark"><svg viewBox="0 0 32 32"><path d="M16 2.7c7.35 0 13.3 5.95 13.3 13.3S23.35 29.3 16 29.3 2.7 23.35 2.7 16 8.65 2.7 16 2.7Z"/><path class="cut" d="M20.8 9.5a8.2 8.2 0 1 0 0 13l-2.25-2.3a5.05 5.05 0 1 1 0-8.4l2.25-2.3Z"/></svg></span><b>Cora AI</b></div>
    <h2 id="authTitle">خوش اومدی</h2><p id="authSubtitle">برای ذخیره گفتگوها وارد حساب خودت شو.</p>
    <div class="auth-tabs"><button class="active" type="button" data-auth-tab="login">ورود</button><button type="button" data-auth-tab="register">ساخت حساب</button></div>
    <form class="auth-form" id="loginForm">
      <label>ایمیل<input name="email" type="email" autocomplete="email" required placeholder="name@example.com"></label>
      <label>رمز عبور<input name="password" type="password" autocomplete="current-password" required placeholder="••••••••••"></label>
      <button class="primary-btn" type="submit">ورود به Cora</button>
    </form>
    <form class="auth-form hidden" id="registerForm">
      <label>نام<input name="name" type="text" autocomplete="name" maxlength="80" required placeholder="نام شما"></label>
      <label>ایمیل<input name="email" type="email" autocomplete="email" required placeholder="name@example.com"></label>
      <label>رمز عبور<input name="password" type="password" autocomplete="new-password" minlength="10" required placeholder="حداقل ۱۰ کاراکتر، شامل حرف و عدد"></label>
      <button class="primary-btn" type="submit">ساخت حساب رایگان</button>
    </form>
    <div class="auth-error" id="authError"></div>
    <p class="auth-terms">با ادامه، شرایط استفاده و سیاست حریم خصوصی را می‌پذیرید.</p>
  </div>
</div>

<div class="toast-stack" id="toasts" aria-live="polite"></div>
<script src="assets/app.js?v=1" defer></script>
</body>
</html>
