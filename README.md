# Cora

Cora یک رابط فارسی RTL برای گفتگو، کدنویسی و ساخت تصویر با PHP + MySQL است. رابط کاربری عمداً فقط برند **Cora** را به کاربر نشان می‌دهد؛ سرویس‌های زیرساختی و توکن‌ها فقط در backend تنظیم می‌شوند.

## نصب سریع روی XAMPP

1. پروژه را داخل `htdocs/Cora-Ai` قرار بده.
2. `database.sql` را در phpMyAdmin اجرا کن.
3. `config.local.php.example` را کپی کن و نام نسخه کپی را `config.local.php` بگذار.
4. توکن را داخل همان فایل وارد کن:

```php
<?php
return [
    'hf_token' => 'hf_xxxxxxxxxxxxxxxxx',
];
```

5. `http://localhost/Cora-Ai/` را باز کن.

`config.local.php` داخل `.gitignore` است و `.htaccess` نیز دسترسی مستقیم وب به آن را می‌بندد. توکن را داخل GitHub کامیت نکن.

## مدل‌ها

### گفتگو

- Qwen 2.5 — مدل متعادل پیش‌فرض
- GPT-OSS 20B — reasoning
- Qwen Coder — برنامه‌نویسی
- Llama 3.2 — سبک و سریع

پاسخ چت از endpoint استریم دریافت می‌شود و UI آن را به‌صورت نرم و حروف‌به‌حروف رندر می‌کند.

### تصویر

مدل پیش‌فرض تصویر:

`stabilityai/stable-diffusion-3-medium-diffusers`

این مدل روی مسیر Text-to-Image مخصوص HF Inference استفاده می‌شود؛ endpoint سازگار OpenAI فقط برای Chat است و برای تصویر استفاده نمی‌شود.

**مهم:** برای اولین استفاده ممکن است لازم باشد در حساب Hugging Face شرایط دسترسی مدل Stability AI را یک‌بار قبول کنی. توکن باید permission مربوط به Inference Providers داشته باشد.

خروجی تصویر در backend با واترمارک `CORA AI` علامت‌گذاری می‌شود؛ اگر PHP GD نصب نباشد، واترمارک نمایشی UI همچنان نشان داده می‌شود ولی برای واترمارک دائمی داخل فایل باید extension `gd` را فعال کنی.

## رابط کاربری

- Dark / Light theme
- رابط خلوت و اختصاصی Cora
- سایدبار تاریخچه و جستجو
- Model Picker با آواتار واقعی سازندگان مدل‌ها
- پنجره جمع‌وجور ساخت تصویر
- انیمیشن neural-network / particles هنگام ساخت تصویر
- حداقل زمان transition برای جلوگیری از پرش ناگهانی کارت تصویر
- Streaming واقعی پاسخ + تایپ نرم حروف‌به‌حروف
- Markdown امن
- Code blocks و Copy
- Syntax highlighting برای PHP, JavaScript, TypeScript, Python, C/C++, C#, Java, Go, Rust, SQL, Bash, Lua, Kotlin, Swift, Ruby و سایر قالب‌های متنی
- تاریخچه، rename، pin و delete گفتگو
- طراحی واکنش‌گرا برای موبایل و دسکتاپ

## سهمیه

پیش‌فرض:

```env
CORA_DAILY_CHAT_LIMIT=60
CORA_DAILY_IMAGE_LIMIT=8
```

این محدودیت داخلی جدا از محدودیت یا اعتبار حساب سرویس مدل است.

## امنیت

- `password_hash` / `password_verify`
- PDO Prepared Statements و خاموش بودن emulate prepares
- CSRF token
- Secure / HttpOnly / SameSite session cookie
- Rate limiting
- سهمیه روزانه per-user
- مالکیت‌سنجی گفتگوها
- whitelist مدل‌ها سمت backend
- CSP و security headers
- عدم ارسال token به JavaScript
- مسدود شدن `config.local.php`, `config.php`, `.env`, `database.sql` و `src/` از وب
- غیرفعال بودن directory listing

هیچ وب‌سایت واقعی را نمی‌توان ۱۰۰٪ «غیرقابل نفوذ» تضمین کرد. برای انتشار عمومی HTTPS، به‌روزرسانی PHP/MariaDB/Apache، backup و مانیتورینگ لاگ‌ها همچنان ضروری است.

## Database

`database.sql` با `utf8mb4` نوشته شده و default فارسی ستون `conversations.title` حذف شده تا روی MariaDB/XAMPPهای قدیمی‌تر به خطای `#1067 Invalid default value` نخورد.

## License

MIT. لوگوی Cora و رابط بصری پروژه برای این پروژه طراحی شده‌اند. آواتار/نام مدل‌ها و سازندگان آن‌ها متعلق به صاحبان مربوطه است.
