<?php
declare(strict_types=1);
require __DIR__ . '/src/bootstrap.php';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#111217">
  <meta name="description" content="Cora AI - دستیار فارسی مبتنی بر Hugging Face برای گفتگو، کدنویسی و ساخت تصویر">
  <title>Cora AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="icon" href="assets/cora-logo.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/app.css?v=2">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-head">
      <button class="brand" id="brandHome" type="button"><img src="assets/cora-logo.svg" alt="Cora"><span><b>Cora</b><small>Hugging Face AI</small></span></button>
      <button class="icon-btn sidebar-close" id="sidebarClose" type="button" aria-label="بستن منو">×</button>
    </div>
    <button class="new-chat" id="newChat" type="button"><span class="plus">+</span><span>گفتگوی جدید</span><kbd>Ctrl K</kbd></button>
    <label class="side-search"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input id="historySearch" type="search" autocomplete="off" placeholder="جستجو در گفتگوها"></label>
    <div class="mode-switcher"><button class="mode-btn active" data-mode="chat" type="button"><span>گفتگو</span><small>Text</small></button><button class="mode-btn" data-mode="image" type="button"><span>تصویر</span><small>FLUX</small></button></div>
    <div class="history-head"><span>تاریخچه</span><button id="refreshHistory" class="tiny-btn" type="button">↻</button></div>
    <div class="history-list" id="historyList"><div class="history-skeleton"><i></i><i></i><i></i></div></div>
    <div class="sidebar-bottom">
      <div class="quota-card"><div class="quota-title"><span>مصرف امروز</span><b id="providerLabel">Hugging Face</b></div><div class="quota-row"><span>گفتگو</span><small id="chatUsageText">0 / 60</small></div><div class="quota-track"><i id="chatUsageBar"></i></div><div class="quota-row"><span>تصویر</span><small id="imageUsageText">0 / 6</small></div><div class="quota-track"><i id="imageUsageBar"></i></div></div>
      <button class="profile-card" id="profileButton" type="button"><span class="avatar" id="sidebarAvatar">C</span><span><b id="sidebarName">حساب کاربری</b><small id="sidebarEmail">برای ذخیره تاریخچه وارد شوید</small></span><em>•••</em></button>
    </div>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
  <main class="main">
    <header class="topbar">
      <div class="top-start"><button class="icon-btn menu-btn" id="menuButton" type="button" aria-label="منو">☰</button><button class="model-chip" id="modelChip" type="button"><span class="model-icon-wrap" id="modelIconWrap"><img id="modelIcon" alt=""></span><span><b id="modelName">Qwen 2.5 7B</b><small id="modelType">Hugging Face · گفتگو</small></span><span class="chev">⌄</span></button></div>
      <div class="top-actions"><button class="icon-btn theme-btn" id="themeToggle" type="button" aria-label="تغییر تم"><span class="sun">☀</span><span class="moon">☾</span></button><button class="clean-btn" id="clearChat" type="button">پاک‌کردن صفحه</button><button class="login-top" id="loginTop" type="button">ورود / ثبت‌نام</button></div>
    </header>
    <section class="chat-scroll" id="chatScroll">
      <div class="welcome" id="welcome"><div class="hero-mark"><img src="assets/cora-logo.svg" alt="Cora"></div><div class="eyebrow"><span></span> Cora AI روی Hugging Face</div><h1>کمکت می‌کنم <span>فکر کنی، بسازی و کد بزنی.</span></h1><p>یک محیط تمیز برای گفتگوی فارسی، برنامه‌نویسی و تولید تصویر؛ با مدل قابل انتخاب و تاریخچه کامل.</p>
        <div class="suggestions"><button class="suggestion" data-prompt="یک معماری امن و حرفه‌ای برای پروژه PHP و MySQL من پیشنهاد بده"><b>معماری نرم‌افزار</b><small>امنیت، ساختار و دیتابیس</small><span>↗</span></button><button class="suggestion" data-prompt="این کد را حرفه‌ای بازنویسی کن و مشکلات امنیتی‌اش را توضیح بده"><b>بررسی کد</b><small>دیباگ و بازنویسی</small><span>↗</span></button><button class="suggestion" data-mode="image" data-prompt="یک شهر آینده‌نگر ایرانی، مینیمال، معماری مدرن با جزئیات کاشی ایرانی، cinematic"><b>ساخت تصویر</b><small>FLUX روی Hugging Face</small><span>↗</span></button><button class="suggestion" data-prompt="یک تحلیل عمیق و ساختاریافته از آینده مدل‌های متن‌باز هوش مصنوعی ارائه کن"><b>تحلیل عمیق</b><small>استدلال و جمع‌بندی</small><span>↗</span></button></div>
      </div><div class="messages" id="messages" aria-live="polite"></div>
    </section>
    <div class="composer-wrap"><div class="image-mode-banner" id="imageModeBanner"><div><b>حالت ساخت تصویر</b><small id="imageModelLabel">FLUX.1 Schnell</small></div><select id="imageSize"><option value="1024x1024">1:1 · 1024</option><option value="768x1024">3:4 · عمودی</option><option value="1024x768">4:3 · افقی</option></select><button id="exitImageMode" type="button">×</button></div>
      <form class="composer" id="composer"><textarea id="prompt" rows="1" maxlength="16000" placeholder="از Cora بپرس..."></textarea><div class="composer-bottom"><div class="composer-tools"><button class="tool-btn" id="imageModeButton" type="button">▧ <span>تصویر</span></button><button class="tool-btn" id="modelMiniButton" type="button">◈ <span id="miniModelName">Qwen 2.5 7B</span></button></div><div class="send-area"><span id="charCount">0 / 16000</span><button class="send-btn" id="sendButton" type="submit"><span class="send-icon">↑</span><span class="stop-icon">■</span></button></div></div></form><p class="composer-note">خروجی مدل ممکن است خطا داشته باشد؛ اطلاعات مهم را بررسی کن.</p>
    </div>
  </main>
</div>
<div class="modal-backdrop" id="modelModal" role="dialog" aria-modal="true"><div class="modal model-modal"><div class="modal-head"><div><b>انتخاب مدل</b><small>مدل‌های واقعی Hugging Face Inference Providers</small></div><button id="modelClose" type="button">×</button></div><div class="model-tabs"><button class="active" data-model-tab="chat" type="button">گفتگو</button><button data-model-tab="image" type="button">تصویر</button></div><div class="model-list" id="modelList"></div><div class="model-note">مدل‌ها از Hugging Face اجرا می‌شوند و از اعتبار همان حساب استفاده می‌کنند. «رایگان» یعنی در محدوده Free tier، نه نامحدود.</div></div></div>
<div class="modal-backdrop" id="authModal" role="dialog" aria-modal="true"><div class="modal auth-modal"><div class="auth-brand"><img src="assets/cora-logo.svg" alt="Cora"><div><b id="authTitle">ورود به Cora</b><small>تاریخچه و تنظیماتت را ذخیره کن</small></div><button id="authClose" type="button">×</button></div><div class="auth-tabs"><button class="active" data-auth-tab="login" type="button">ورود</button><button data-auth-tab="register" type="button">ثبت‌نام</button></div><div class="auth-error" id="authError"></div><form id="loginForm" class="auth-form"><label>ایمیل<input name="email" type="email" required autocomplete="email" placeholder="you@example.com"></label><label>رمز عبور<input name="password" type="password" required autocomplete="current-password" minlength="8"></label><button type="submit">ورود</button></form><form id="registerForm" class="auth-form hidden"><label>نام<input name="name" type="text" required minlength="2" maxlength="80"></label><label>ایمیل<input name="email" type="email" required autocomplete="email"></label><label>رمز عبور<input name="password" type="password" required minlength="8" autocomplete="new-password"></label><button type="submit">ساخت حساب</button></form></div></div>
<div class="toasts" id="toasts"></div><script src="assets/app.js?v=2" defer></script>
</body></html>
