# سجل التغييرات (Changelog)

كل التغييرات المهمة في هذه الحزمة موثّقة هنا.
الصيغة مبنية على [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)، والإصدارات تتبع [Semantic Versioning](https://semver.org/).

## [Unreleased]

### أُضيف
- GitHub Actions: تشغيل الاختبارات تلقائيًا على PHP 8.1–8.5 وLaravel 10–13، والتحقق من أن ملفات `data/*.json` مطابقة لما يولّده `tools/build.py`.
- شارة حالة الاختبارات في الـ README.
- توثيق وأمثلة استخدام بالإنجليزية في الـ README.
- دعم PHPUnit 13 في `require-dev`.

## [1.0.0] - 2026-10-19

أول إصدار.

### أُضيف
- **البيانات**: 69 ولاية و549 دائرة و1541 بلدية بالعربية والحروف اللاتينية، وفق التقسيم الإقليمي لسنة 2026 (القانون 26-06 والمرسوم الرئاسي 26-206).
- **دعم التقسيم القديم** (58 ولاية / 548 دائرة) عبر `ALGERIA_DIVISION=58`، مع أرقام بلديات ثابتة في التقسيمين.
- **الرموز البريدية**: الرمز البريدي الرئيسي لـ 1505 بلديات، وجدول `postal_codes` بـ 3752 رمزًا (مكتب بريد) مرتبطة ببلدياتها.
- Migrations وSeeder قابل لإعادة التشغيل (`AlgeriaSeeder`).
- نماذج Eloquent: `Wilaya` و`Daira` و`Commune` و`PostalCode` مع العلاقات، ومنها `parent`/`children` للولايات المستحدثة و`legacyWilaya` للبلديات.
- Facade `Algeria` يعمل بدون قاعدة بيانات، مع بحث يتجاهل الحالة والتشكيل والهمزات.
- أوامر Artisan: `algeria:install` و`algeria:seed --division=58|69`.
- قواعد تحقق: `ValidWilaya` و`ValidDaira` و`ValidCommune` و`ValidPostalCode`، مع التحقق من الانتماء، ورسائل بالعربية والفرنسية والإنجليزية.
- API JSON اختياري (`ALGERIA_ROUTES=true`) للقوائم المتسلسلة ولجلب البلدية من رمزها البريدي.
- دعم Laravel 10 و11 و12 و13، وPHP 8.1 فما فوق.

[Unreleased]: https://github.com/djouamaah/laravel-algeria/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/djouamaah/laravel-algeria/releases/tag/v1.0.0
