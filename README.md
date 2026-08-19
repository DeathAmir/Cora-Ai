# Cora AI

Cora AI یک رابط فارسی RTL برای گفتگو و ساخت تصویر با PHP + MySQL است که مستقیماً به **Hugging Face Inference Providers** وصل می‌شود.

## نصب سریع روی XAMPP

1. پوشه پروژه را داخل `htdocs/Cora-Ai` قرار بده.
2. `database.sql` را در phpMyAdmin اجرا کن.
3. فایل `config.local.php.example` را کپی کن و اسم نسخه کپی را `config.local.php` بگذار.
4. داخل `config.local.php` توکن Hugging Face خودت را وارد کن:

```php
<?php
return [
    'hf_token' => 'hf_xxxxxxxxxxxxxxxxx',
];
```

5. آدرس `http://localhost/Cora-Ai/` را باز کن.

> `config.local.php` در `.gitignore` قرار دارد و `.htaccess` نیز دسترسی مستقیم وب به آن را مسدود می‌کند. توکن را داخل GitHub کامیت نکن.

## Hugging Face

- Chat: `https://router.huggingface.co/v1/chat/completions`
- Image: HF Inference provider با `black-forest-labs/FLUX.1-schnell`
- پیش‌فرض گفتگو: `Qwen/Qwen2.5-7B-Instruct:cheapest`
- مدل‌ها از داخل UI قابل انتخاب‌اند.

Hugging Face Free tier محدود است و رایگان نامحدود نیست. Cora به صورت پیش‌فرض سهمیه داخلی زیر را اعمال می‌کند:

- 60 پاسخ متنی در روز برای هر کاربر
- 6 تصویر در روز برای هر کاربر

این اعداد از Environment Variable قابل تغییر هستند:

```env
CORA_DAILY_CHAT_LIMIT=60
CORA_DAILY_IMAGE_LIMIT=6
```

## قابلیت‌ها

- ثبت‌نام و ورود ایمیلی با `password_hash`
- PDO Prepared Statements
- CSRF protection
- Session cookies امن
- Rate limiting و سهمیه روزانه
- تاریخچه، جستجو، rename، pin و delete گفتگو
- انتخاب مدل واقعی Hugging Face با آیکون برند
- تم روز و شب
- FLUX image generation card
- Thinking/loading UI بدون نمایش chain-of-thought داخلی مدل
- Markdown renderer بدون اجرای HTML خام
- Code blocks و Copy
- Syntax highlighting برای JavaScript, TypeScript, PHP, Python, C, C++, C#, Java, Go, Rust, SQL, Bash, Lua, Kotlin, Swift, Ruby, JSON, YAML, HTML و CSS
- رابط واکنش‌گرا برای موبایل و دسکتاپ

## امنیت

هیچ وب‌سایتی «غیرقابل نفوذ» نیست. این پروژه چند لایه دفاعی دارد، اما برای انتشار عمومی همچنان باید PHP/MySQL/Apache را به‌روز نگه داری، HTTPS فعال کنی، بکاپ داشته باشی و لاگ‌ها را بررسی کنی.

## نکته دیتابیس

در `database.sql` برای سازگاری بهتر با MariaDB/XAMPP، مقدار پیش‌فرض فارسی ستون `conversations.title` حذف شده و `utf8mb4` به صورت صریح تنظیم شده است.

## License

MIT. نام‌ها و لوگوهای Hugging Face و سازندگان مدل‌ها متعلق به صاحبان علامت تجاری مربوطه هستند.
