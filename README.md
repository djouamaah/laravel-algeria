# Laravel Algeria — ولايات، دوائر وبلديات الجزائر

مكتبة لارافل تضم **جميع ولايات ودوائر وبلديات الجزائر** بالعربية والحروف اللاتينية، وفق التقسيم الإقليمي الجديد لسنة 2026:

| التقسيم | الولايات | الدوائر | البلديات |
|---|---|---|---|
| **2026** (القانون 26-06 والمرسوم الرئاسي 26-206) — الافتراضي | 69 | 549 | 1541 |
| 2019 (القانون 19-12) | 58 | 548 | 1541 |

- Migrations + Seeder + Eloquent Models (`Wilaya`, `Daira`, `Commune`) مع العلاقات.
- Facade `Algeria` يعمل **بدون قاعدة بيانات** (من ملفات JSON مدمجة).
- قواعد تحقق جاهزة: `ValidWilaya`، `ValidDaira`، `ValidCommune` (مع التحقق من الانتماء)، ورسائل بالعربية والفرنسية والإنجليزية.
- API JSON اختياري للقوائم المتسلسلة (ولاية ← دائرة ← بلدية).
- بحث يتجاهل الحالة والتشكيل والهمزات (`بجاية` = `Béjaïa` = `bejaia`).
- دعم التقسيمين 58 و69، و**أرقام البلديات ثابتة في كليهما**، فبياناتك المخزّنة بـ `commune_id` لا تتأثر عند الانتقال.

يدعم Laravel 10 و11 و12 و13، وPHP 8.1 فما فوق.

---

## التثبيت

بعد نشرها على Packagist:

```bash
composer require djouamaah/laravel-algeria
```

أو محليًا قبل النشر، أضف في `composer.json` الخاص بمشروعك:

```json
"repositories": [
    { "type": "path", "url": "../laravel-algeria" }
]
```

ثم:

```bash
composer require djouamaah/laravel-algeria:@dev
```

ثم أمر واحد ينشر الإعدادات وينفّذ الـ migrations ويملأ الجداول:

```bash
php artisan algeria:install
```

> أو يدويًا: `php artisan migrate` ثم `php artisan algeria:seed`.
> أو داخل `DatabaseSeeder`:
> `$this->call(\Djouamaah\Algeria\Database\AlgeriaSeeder::class);`

---

## الإعدادات

`config/algeria.php` (يُنشر بـ `php artisan vendor:publish --tag=algeria-config`):

```php
'division' => env('ALGERIA_DIVISION', 69),   // 69 أو 58

'tables' => [
    'wilayas' => 'wilayas',
    'dairas' => 'dairas',
    'communes' => 'communes',
],

'routes' => [
    'enabled' => env('ALGERIA_ROUTES', false),
    'prefix' => 'api/algeria',
    'middleware' => ['api'],
],
```

للتبديل بين التقسيمين (الأمر يحدّث البيانات في مكانها، ويمكن تشغيله أكثر من مرة):

```bash
php artisan algeria:seed --division=58
php artisan algeria:seed --division=69
```

> ⚠️ عند الرجوع إلى 58 تُحذف الولايات 59–69. إذا كانت لديك جداول بمفاتيح أجنبية تشير إليها فعدّلها أولًا.

---

## بنية الجداول

**wilayas**: `id` (= رقم الولاية)، `code` (`"01"`…`"69"`)، `name`، `name_ar`، `parent_id` (الولاية الأم للولايات الجديدة)

**dairas**: `id`، `wilaya_id`، `name`، `name_ar`

**communes**: `id`، `daira_id`، `wilaya_id`، `legacy_wilaya_id` (الولاية في تقسيم 58)، `name`، `name_ar`

---

## الاستعمال

### Eloquent

```php
use Djouamaah\Algeria\Models\Wilaya;
use Djouamaah\Algeria\Models\Daira;
use Djouamaah\Algeria\Models\Commune;

$wilaya = Wilaya::code(68)->first();      // بوسعادة
$wilaya->dairas;                          // 8 دوائر
$wilaya->communes;                        // 23 بلدية
$wilaya->parent;                          // المسيلة (الولاية الأم)
Wilaya::find(28)->children;               // الولايات المستحدثة من المسيلة

Commune::search('بجاية')->first()->daira->wilaya;
Wilaya::orderByName()->get();             // ترتيب حسب لغة التطبيق

app()->setLocale('ar');
$wilaya->localized_name;                  // "بوسعادة"  (و"Bou Saâda" في باقي اللغات)
```

ربط نموذجك:

```php
// migration
$table->unsignedSmallInteger('commune_id');
$table->foreign('commune_id')->references('id')->on('communes');

// App\Models\User
public function commune()
{
    return $this->belongsTo(\Djouamaah\Algeria\Models\Commune::class);
}
```

### Facade (بدون قاعدة بيانات)

```php
use Djouamaah\Algeria\Facades\Algeria;

Algeria::wilayas();                 // Collection من 69 ولاية
Algeria::wilaya('16');              // ['id' => 16, 'code' => '16', 'name' => 'Alger', 'name_ar' => 'الجزائر', ...]
Algeria::dairas(16);                // دوائر ولاية الجزائر
Algeria::communes(16);              // بلديات ولاية الجزائر
Algeria::communesOfDaira(549);      // بلديات دائرة
Algeria::commune(1);
Algeria::search('tizi', wilaya: 15);
Algeria::withDivision(58)->wilayas();  // التقسيم القديم
```

### التحقق من المدخلات

```php
use Djouamaah\Algeria\Rules\ValidWilaya;
use Djouamaah\Algeria\Rules\ValidDaira;
use Djouamaah\Algeria\Rules\ValidCommune;

$request->validate([
    'wilaya_id'  => ['required', new ValidWilaya],
    'daira_id'   => ['required', new ValidDaira($request->wilaya_id)],
    'commune_id' => ['required', new ValidCommune(daira: $request->daira_id)],
]);
```

### API للقوائم المتسلسلة

فعّلها بـ `ALGERIA_ROUTES=true` في `.env`:

| الطريق | الوصف |
|---|---|
| `GET /api/algeria/wilayas` | كل الولايات |
| `GET /api/algeria/wilayas/{wilaya}` | ولاية واحدة |
| `GET /api/algeria/wilayas/{wilaya}/dairas` | دوائر ولاية |
| `GET /api/algeria/wilayas/{wilaya}/communes?q=` | بلديات ولاية (مع بحث اختياري) |
| `GET /api/algeria/dairas/{daira}/communes` | بلديات دائرة |

مثال Blade:

```blade
<select id="wilaya" name="wilaya_id">
    @foreach (Algeria::wilayas() as $w)
        <option value="{{ $w['id'] }}">{{ $w['code'] }} - {{ $w['name_ar'] }}</option>
    @endforeach
</select>
<select id="daira" name="daira_id"></select>
<select id="commune" name="commune_id"></select>

<script>
const fill = (select, rows) =>
    select.innerHTML = '<option value="">--</option>' +
        rows.map(r => `<option value="${r.id}">${r.name_ar}</option>`).join('');

wilaya.onchange = async () => {
    fill(daira, (await (await fetch(`/api/algeria/wilayas/${wilaya.value}/dairas`)).json()).data);
    fill(commune, []);
};
daira.onchange = async () =>
    fill(commune, (await (await fetch(`/api/algeria/dairas/${daira.value}/communes`)).json()).data);
</script>
```

---

## الولايات الجديدة (2026)

| الرمز | الولاية | الولاية الأم | البلديات |
|---|---|---|---|
| 59 | آفلو Aflou | الأغواط | 12 |
| 60 | بريكة Barika | باتنة | 8 |
| 61 | القنطرة El Kantara | بسكرة | 5 |
| 62 | بئر العاتر Bir El Ater | تبسة | 4 |
| 63 | العريشة El Aricha | تلمسان | 4 |
| 64 | قصر الشلالة Ksar Chellala | تيارت | 6 |
| 65 | عين وسارة Aïn Ouessara | الجلفة | 10 |
| 66 | مسعد Messaad | الجلفة | 8 |
| 67 | قصر البخاري Ksar El Boukhari | المدية | 21 |
| 68 | بوسعادة Bou Saâda | المسيلة | 23 |
| 69 | الأبيض سيدي الشيخ El Abiodh Sidi Cheikh | البيض | 7 |

---

## مصدر البيانات وتحديثها

- القاعدة (58 ولاية / 548 دائرة / 1541 بلدية): [othmanus/algeria-cities](https://github.com/othmanus/algeria-cities)، بعد تنظيف المسافات وتصحيح بعض الأسماء.
- الولايات 59–69: أرقامها وأسماؤها من المرسوم الرئاسي 26-206 (الجريدة الرسمية العدد 40، 2026)، وبلدياتها من قوائم القانون 26-06. كل ولاية جديدة أخذت دوائر كاملة من ولايتها الأم.
- **استثناء:** بلديتا العريشة والقور كانتا ضمن دائرة سبدو، فوُضعتا في دائرة جديدة «العريشة» (رقم 549) ضمن الولاية 63، وبقيت سبدو في تلمسان. راجع هذا مع نص المرسوم التنفيذي للتنظيم الإداري للولايات الجديدة عند صدوره نهائيًا.
- إعادة توزيع الدوائر داخل الولايات الجديدة بمراسيم لاحقة (إن وُجدت) غير مدرجة بعد.

البيانات في `data/*.json` مولّدة بالسكربت:

```bash
python tools/build.py
```

لتعديل أي اسم أو نقل دائرة: عدّل `tools/build.py` (أو المصدر) ثم أعد التوليد ثم `php artisan algeria:seed`.

## الاختبارات

```bash
composer install
composer test
```

## الترخيص

MIT
