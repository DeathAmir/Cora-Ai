# Cora AI

یک رابط کامل فارسی/RTL برای چت هوش مصنوعی و ساخت تصویر با PHP + MySQL + JavaScript + CSS.

## قابلیت‌ها

- رابط مدرن شبیه محصولات حرفه‌ای AI با Sidebar و تاریخچه گفتگو
- ثبت‌نام و ورود با ایمیل و `password_hash`
- چت متنی با Ollama یا APIهای OpenAI-compatible
- ساخت تصویر با endpoint سازگار با OpenAI Images API
- ذخیره گفتگوها و پیام‌ها در MySQL
- جستجو، پین، تغییر نام و حذف گفتگو
- Markdown، Code Block، دکمه کپی و Syntax Highlight پایه
- وضعیت Thinking و کارت اختصاصی Image Generation
- تم تاریک/روشن و طراحی Responsive برای موبایل
- CSRF protection، Session hardening، Prepared Statements و Rate limiting
- CSP / HSTS / X-Frame-Options / nosniff / Referrer Policy
- GitHub Actions برای PHP lint

## نیازمندی‌ها

- PHP 8.1+ (پیشنهاد: PHP 8.2)
- MySQL 8+ یا MariaDB جدید
- PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `json`
- Apache با `mod_rewrite` و `mod_headers` پیشنهاد می‌شود

## نصب سریع در XAMPP / WAMP

1. پروژه را داخل `htdocs/Cora-Ai` قرار بده.
2. Apache و MySQL را روشن کن.
3. فایل `database.sql` را در phpMyAdmin اجرا کن.
4. تنظیم پیش‌فرض دیتابیس پروژه همین است:

```text
host: 127.0.0.1
port: 3306
database: coradb
user: root
password: (empty)
```

5. سایت را باز کن:

```text
http://localhost/Cora-Ai/
```

## اتصال مدل متنی محلی با Ollama

پیش‌فرض پروژه برای متن:

```text
provider: ollama
base URL: http://127.0.0.1:11434
model: qwen2.5:7b
```

مثال:

```bash
ollama pull qwen2.5:7b
ollama serve
```

بعد از آن Cora مستقیماً از مدل محلی پاسخ می‌گیرد.

## اتصال API سازگار با OpenAI

متغیرهای محیطی زیر را در Apache/PHP یا سیستم تنظیم کن:

```text
CORA_AI_PROVIDER=openai_compatible
CORA_AI_BASE_URL=https://your-provider.example
CORA_AI_API_KEY=your-secret-key
CORA_TEXT_MODEL=your-chat-model
CORA_IMAGE_MODEL=your-image-model
```

کلید API را داخل Git commit نکن.

## ساخت تصویر

برای Image Generation باید provider روی `openai_compatible` باشد و سرویس انتخابی endpoint زیر را پشتیبانی کند:

```text
POST /v1/images/generations
```

خروجی `url` و `b64_json` پشتیبانی می‌شود.

## امنیت

هیچ وب‌سایتی را نمی‌توان به شکل واقعی «۱۰۰٪ غیرقابل نفوذ» تضمین کرد. این پروژه چند لایه دفاعی دارد:

- PDO prepared statements و غیرفعال بودن emulated prepares
- CSRF token روی عملیات POST
- Session ID regeneration بعد از login/register
- Cookieهای HttpOnly + SameSite=Strict + Secure روی HTTPS
- Rate limit برای login/chat/image
- بررسی مالکیت Conversation در تمام عملیات
- محدودیت طول ورودی و JSON request size
- Escape کردن خروجی کاربر و Markdown renderer کنترل‌شده
- CSP و سایر Security Headerها
- جلوگیری از Directory Listing و دسترسی مستقیم به `config.php`, `database.sql`, `.env` و `src/` روی Apache
- عدم hard-code کردن API key

برای Production حتماً این موارد را نیز انجام بده:

- HTTPS واقعی
- برای MySQL یک کاربر جدا با رمز قوی بساز و از `root` استفاده نکن
- `CORA_DEBUG=0`
- API key فقط در Environment Variable یا Secret Manager
- محدودکردن Apache/Nginx و Firewall
- به‌روزرسانی منظم PHP/MySQL/OpenSSL
- Backup و log monitoring

## ساختار

```text
Cora-Ai/
├── api/
│   ├── auth.php
│   ├── chat.php
│   ├── history.php
│   ├── image.php
│   └── session.php
├── assets/
│   ├── app.css
│   └── app.js
├── src/
│   ├── AiClient.php
│   └── bootstrap.php
├── .github/workflows/php-lint.yml
├── .env.example
├── .htaccess
├── config.php
├── database.sql
├── index.php
└── README.md
```

## License

MIT
