# Laravel Algeria — ولايات، دوائر وبلديات الجزائر

[![Tests](https://github.com/djouamaah/laravel-algeria/actions/workflows/tests.yml/badge.svg)](https://github.com/djouamaah/laravel-algeria/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/djouamaah/laravel-algeria)](https://packagist.org/packages/djouamaah/laravel-algeria)
[![Total Downloads](https://img.shields.io/packagist/dt/djouamaah/laravel-algeria)](https://packagist.org/packages/djouamaah/laravel-algeria)
[![License](https://img.shields.io/packagist/l/djouamaah/laravel-algeria)](LICENSE)

**[English documentation ↓](#english)**

مكتبة لارافل تضم **جميع ولايات ودوائر وبلديات الجزائر** بالعربية والحروف اللاتينية، وفق التقسيم الإقليمي الجديد لسنة 2026:

| التقسيم | الولايات | الدوائر | البلديات |
|---|---|---|---|
| **2026** (القانون 26-06 والمرسوم الرئاسي 26-206) — الافتراضي | 69 | 549 | 1541 |
| 2019 (القانون 19-12) | 58 | 548 | 1541 |

- Migrations + Seeder + Eloquent Models (`Wilaya`, `Daira`, `Commune`, `PostalCode`) مع العلاقات.
- **الرموز البريدية**: الرمز البريدي الرئيسي لكل بلدية، وجدول بـ 3752 رمزًا بريديًا (مكتب بريد) مرتبطة ببلدياتها.
- **الإحداثيات**: خط العرض والطول لكل البلديات الـ 1541، مع البحث عن أقرب بلدية لنقطة GPS.
- Facade `Algeria` يعمل **بدون قاعدة بيانات** (من ملفات JSON مدمجة).
- قواعد تحقق جاهزة: `ValidWilaya`، `ValidDaira`، `ValidCommune`، `ValidPostalCode` (مع التحقق من الانتماء)، ورسائل بالعربية والفرنسية والإنجليزية.
- API JSON اختياري للقوائم المتسلسلة (ولاية ← دائرة ← بلدية).
- بحث يتجاهل الحالة والتشكيل والهمزات (`بجاية` = `Béjaïa` = `bejaia`).
- دعم التقسيمين 58 و69، و**أرقام البلديات ثابتة في كليهما**، فبياناتك المخزّنة بـ `commune_id` لا تتأثر عند الانتقال.

يدعم Laravel 10 و11 و12 و13، وPHP 8.1 فما فوق.

---

## التثبيت

```bash
composer require djouamaah/laravel-algeria
```

يُسجَّل الـ Service Provider والـ Facade `Algeria` تلقائيًا (Laravel package discovery).

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
    'postal_codes' => 'postal_codes',
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

**communes**: `id`، `daira_id`، `wilaya_id`، `legacy_wilaya_id` (الولاية في تقسيم 58)، `name`، `name_ar`، `postal_code` (الرمز البريدي الرئيسي، قد يكون `null`)، `latitude`، `longitude`

**postal_codes**: `code` (مفتاح أساسي، 5 أرقام مثل `"16000"`)، `commune_id`، `name`، `name_ar` (اسم مكتب البريد)

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

### الرموز البريدية

```php
use Djouamaah\Algeria\Models\PostalCode;

$commune->postal_code;                       // "16000" (الرمز الرئيسي)
$commune->postalCodes;                       // كل مكاتب البريد في البلدية
Commune::where('postal_code', '16000')->first();
PostalCode::find('16000')->commune->wilaya;  // من الرمز البريدي إلى البلدية والولاية
```

### الإحداثيات

```php
$oran = Commune::where('name', 'Oran')->first();
$oran->latitude;                     // 35.696944
$oran->longitude;                    // -0.633056
$oran->distanceTo(36.7731, 3.0589);  // ≈ 352 كم إلى الجزائر العاصمة

Algeria::nearest(36.7731, 3.0589);           // أقرب بلدية لنقطة GPS (مع distance_km)
Algeria::nearest(36.7731, 3.0589, limit: 5); // أقرب 5 بلديات
Algeria::nearest($lat, $lng, wilaya: 16);    // داخل ولاية واحدة
Algeria::distance($lat1, $lng1, $lat2, $lng2); // المسافة بين نقطتين بالكيلومتر
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
Algeria::postalCode('16000');           // ['code' => '16000', 'commune_id' => ..., 'name' => 'Alger', ...]
Algeria::communeByPostalCode('16000');  // بلدية الجزائر الوسطى
Algeria::postalCodes($communeId);       // رموز بلدية معيّنة
Algeria::withDivision(58)->wilayas();  // التقسيم القديم
```

### التحقق من المدخلات

```php
use Djouamaah\Algeria\Rules\ValidWilaya;
use Djouamaah\Algeria\Rules\ValidDaira;
use Djouamaah\Algeria\Rules\ValidCommune;
use Djouamaah\Algeria\Rules\ValidPostalCode;

$request->validate([
    'wilaya_id'  => ['required', new ValidWilaya],
    'daira_id'   => ['required', new ValidDaira($request->wilaya_id)],
    'commune_id' => ['required', new ValidCommune(daira: $request->daira_id)],
    'postal_code' => ['nullable', new ValidPostalCode(commune: $request->commune_id)],
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
| `GET /api/algeria/communes/nearest?lat=&lng=&limit=` | أقرب البلديات لنقطة GPS |
| `GET /api/algeria/communes/{commune}/postal-codes` | الرموز البريدية لبلدية |
| `GET /api/algeria/postal-codes/{code}` | رمز بريدي مع بلديته ودائرته وولايته |

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
- الولايات 59–69: أرقامها وأسماؤها من المرسوم الرئاسي 26-206 (الجريدة الرسمية العدد 40، 2026).
- **البلديات مطابقة لنص القانون 26-06** (الجريدة الرسمية العدد 25، 5 أفريل 2026) في كل الولايات الـ 21 التي غيّرها: الولايات الـ 11 الجديدة والولايات الأم العشر. نص المواد في `tools/source-law-26-06.md`، و`tools/build.py` يتوقف إذا اختلفت أي بلدية عنه.
- كل ولاية جديدة أخذت دوائر كاملة من ولايتها الأم، إلا العريشة: بلديتا العريشة والقور كانتا ضمن دائرة سبدو، فصارتا دائرة «العريشة» (رقم 549) إلى جانب دائرة سيدي الجيلالي، وبقيت سبدو في تلمسان.
- الدوائر: دوائر بوسعادة مطابقة للمرسوم التنفيذي المنشور في الجريدة الرسمية العدد 52 (جويلية 2026)، ودوائر الولايات العشر الأخرى مطابقة لصفحاتها في ويكيبيديا العربية. لم أجد نص المرسوم التنفيذي كاملًا.

### الرموز البريدية

- المصدر: ملف مكاتب البريد في [othmanus/algeria-cities](https://github.com/othmanus/algeria-cities) (3940 سطرًا). استُبعدت الأسطر بلا رمز، وأُعيد ربط مكاتب ربطها المصدر ببلدية تحمل الاسم نفسه في ولاية أخرى، واستُبعد ما تعذّر ربطه. بقي **3752 رمزًا**.
- `postal_code` في البلدية هو رمز مكتب البريد الرئيسي فيها: مكتب الولاية الرئيسي (`xx000`) إن وُجد، ثم المكتب الذي يحمل اسم البلدية، ثم الأقرب اسمًا، وإلا فأصغر رمز في البلدية.
- **1505 بلدية من 1541** لها رمز بريدي، و36 بلدية بقيمة `null` لأن المصدر لا يذكر لها أي مكتب بريد.
- الرموز البريدية تبدأ برقم الولاية في تقسيم 1984 (48 ولاية) ولا تتغير بالتقسيم الجديد: بلديات ولاية بوسعادة (68) رموزها تبدأ بـ `28`.

### الإحداثيات

- ثلاثة مصادر: [Wikidata](https://www.wikidata.org) (CC0) و[kossa/algerian-cities](https://github.com/kossa/algerian-cities) و[geoalgeria](https://github.com/yasserstudio/geoalgeria) (كلاهما MIT).
- **1518 بلدية** أكّد مصدران موقعها (فرق أقل من 10 كم)، و**9** صُحّحت يدويًا (أغلبها بلديات تحمل اسم بلدية في ولاية أخرى، مثل بسباس والمعمورة ومنعة)، و**14** من مصدر واحد بعد التحقق من قربها من بلديات دائرتها.
- الإحداثيات تمثّل مركز البلدية تقريبيًا، ولا تمثّل حدودها.
- تراخيص المصادر في [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).

البيانات في `data/*.json` مولّدة بالسكربت:

```bash
python tools/build.py
```

لتعديل أي اسم أو نقل دائرة: عدّل `tools/build.py` (أو المصدر) ثم أعد التوليد ثم `php artisan algeria:seed`.

---

## English

All Algerian **wilayas (provinces), dairas (districts), communes (municipalities) and postal codes** for Laravel, in Arabic and Latin script.

| Division | Wilayas | Dairas | Communes |
|---|---|---|---|
| **2026** (law 26-06, decree 26-206), default | 69 | 549 | 1541 |
| 2019 (law 19-12) | 58 | 548 | 1541 |

Every commune has a main postal code (when known) and latitude/longitude coordinates.
Commune ids are the same in both divisions, so a stored `commune_id` stays valid if you switch.
Supports Laravel 10–13 and PHP 8.1+.

### Installation

```bash
composer require djouamaah/laravel-algeria
php artisan algeria:install
```

`algeria:install` publishes `config/algeria.php`, runs the migrations and seeds the tables.
You can also run `php artisan migrate` and `php artisan algeria:seed` yourself, or call the seeder from your `DatabaseSeeder`:

```php
$this->call(\Djouamaah\Algeria\Database\AlgeriaSeeder::class);
```

### Configuration

```dotenv
ALGERIA_DIVISION=69   # 69 (2026, default) or 58 (2019)
ALGERIA_ROUTES=true   # enable the JSON API (disabled by default)
```

Switch division at any time (updates rows in place, safe to re-run):

```bash
php artisan algeria:seed --division=58
php artisan algeria:seed --division=69
```

> Switching to 58 deletes wilayas 59–69. Update any foreign keys of yours that point to them first.

### Eloquent models

```php
use Djouamaah\Algeria\Models\Wilaya;
use Djouamaah\Algeria\Models\Commune;
use Djouamaah\Algeria\Models\PostalCode;

$wilaya = Wilaya::code(16)->first();     // Alger
$wilaya->dairas;                         // dairas of the wilaya
$wilaya->communes;                       // communes of the wilaya

Wilaya::find(68)->parent;                // M'Sila: the wilaya Bou Saâda was split from
Wilaya::find(28)->children;              // wilayas created from M'Sila

$commune = Commune::search('Bejaia')->first();
$commune->daira->wilaya->name;           // "Béjaïa"
$commune->postal_code;                   // "06000", main postal code (nullable)
$commune->postalCodes;                   // every post office of the commune

PostalCode::find('16000')->commune->name;  // "Alger Centre"

$commune->latitude;                      // 36.751111 (float)
$commune->longitude;                     // 5.064167 (float)
$commune->distanceTo(36.7731, 3.0589);   // kilometres to a point

Wilaya::orderByName()->get();            // sorted by name_ar when the locale is "ar", by name otherwise
app()->setLocale('ar');
$wilaya->localized_name;                 // "الجزائر" ("Alger" in any other locale)
```

Link your own model to a commune:

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

### Facade (no database needed)

The `Algeria` facade reads the bundled JSON files, so it works even before you migrate. Every row is a plain array.

```php
use Djouamaah\Algeria\Facades\Algeria;

Algeria::wilayas();                       // Collection of 69 wilayas
Algeria::wilaya('16');                    // ['id' => 16, 'code' => '16', 'name' => 'Alger', 'name_ar' => 'الجزائر', 'parent_id' => null]
Algeria::dairas(16);                      // dairas of Algiers
Algeria::communes(16);                    // communes of Algiers
Algeria::communesOfDaira(193);            // communes of a daira
Algeria::commune(556);                    // Alger Centre

Algeria::search('tizi');                  // ignores case, accents and hamza: 'بجاية' = 'Béjaïa' = 'bejaia'
Algeria::search('bab', wilaya: 16);       // limited to one wilaya

Algeria::postalCode('16000');             // ['code' => '16000', 'commune_id' => 556, 'name' => 'Alger', 'name_ar' => '...']
Algeria::communeByPostalCode('16000');    // Alger Centre
Algeria::postalCodes(556);                // postal codes of a commune

Algeria::nearest(36.7731, 3.0589);              // the commune closest to a GPS point, with 'distance_km'
Algeria::nearest(36.7731, 3.0589, limit: 5);    // the 5 closest communes
Algeria::nearest($lat, $lng, wilaya: 16);       // only within one wilaya
Algeria::distance($lat1, $lng1, $lat2, $lng2);  // great-circle distance in km

Algeria::withDivision(58)->wilayas();     // the 2019 division (58 wilayas)
```

### Validation rules

The rules use the bundled data (no database query) and check that each level belongs to the one above it.
Error messages are available in English, French and Arabic.

```php
use Djouamaah\Algeria\Rules\ValidWilaya;
use Djouamaah\Algeria\Rules\ValidDaira;
use Djouamaah\Algeria\Rules\ValidCommune;
use Djouamaah\Algeria\Rules\ValidPostalCode;

$request->validate([
    'wilaya_id'   => ['required', new ValidWilaya],
    'daira_id'    => ['required', new ValidDaira($request->wilaya_id)],
    'commune_id'  => ['required', new ValidCommune(daira: $request->daira_id)],
    'postal_code' => ['nullable', new ValidPostalCode(commune: $request->commune_id)],
]);
```

`ValidCommune` also accepts `wilaya:` instead of `daira:`.

### JSON API

Set `ALGERIA_ROUTES=true` in `.env`. Responses are cached by the browser for one day.

| Route | Returns |
|---|---|
| `GET /api/algeria/wilayas` | all wilayas |
| `GET /api/algeria/wilayas/{wilaya}` | one wilaya |
| `GET /api/algeria/wilayas/{wilaya}/dairas` | dairas of a wilaya |
| `GET /api/algeria/wilayas/{wilaya}/communes?q=` | communes of a wilaya, optional search |
| `GET /api/algeria/dairas/{daira}/communes` | communes of a daira |
| `GET /api/algeria/communes/nearest?lat=&lng=&limit=` | closest communes to a GPS point (`limit` 1–50) |
| `GET /api/algeria/communes/{commune}/postal-codes` | postal codes of a commune |
| `GET /api/algeria/postal-codes/{code}` | a postal code with its commune, daira and wilaya |

Example: `GET /api/algeria/postal-codes/16000`

```json
{
    "data": {
        "code": "16000",
        "commune_id": 556,
        "name": "Alger",
        "name_ar": "الجزائر - القبّاضة الرّئيسيّة",
        "commune": { "id": 556, "daira_id": 193, "wilaya_id": 16, "legacy_wilaya_id": 16, "name": "Alger Centre", "name_ar": "الجزائر الوسطى", "postal_code": "16000" },
        "daira": { "id": 193, "wilaya_id": 16, "name": "Sidi M'hamed", "name_ar": "سيدي امحمد" },
        "wilaya": { "id": 16, "code": "16", "name": "Alger", "name_ar": "الجزائر", "parent_id": null }
    }
}
```

Cascading selects in Blade:

```blade
<select id="wilaya" name="wilaya_id">
    @foreach (Algeria::wilayas() as $w)
        <option value="{{ $w['id'] }}">{{ $w['code'] }} - {{ $w['name'] }}</option>
    @endforeach
</select>
<select id="daira" name="daira_id"></select>
<select id="commune" name="commune_id"></select>

<script>
const fill = (select, rows) =>
    select.innerHTML = '<option value="">--</option>' +
        rows.map(r => `<option value="${r.id}">${r.name}</option>`).join('');

wilaya.onchange = async () => {
    fill(daira, (await (await fetch(`/api/algeria/wilayas/${wilaya.value}/dairas`)).json()).data);
    fill(commune, []);
};
daira.onchange = async () =>
    fill(commune, (await (await fetch(`/api/algeria/dairas/${daira.value}/communes`)).json()).data);
</script>
```

### Data notes

- Base data (58 wilayas, 1541 communes) and post offices come from [othmanus/algeria-cities](https://github.com/othmanus/algeria-cities), cleaned up.
- Wilayas 59–69 follow presidential decree 26-206 (names, numbers, chefs-lieux).
- Communes match the text of law 26-06 (JO n° 25, 5 April 2026) for all 21 wilayas it changed: the 11 new ones and their 10 parent wilayas. The articles are kept in `tools/source-law-26-06.md`, and `tools/build.py` fails if any commune drifts from them.
- Each new wilaya took whole dairas from its parent, except El Aricha: its communes El Aricha and El Gor were in Sebdou daira, so they form the El Aricha daira (id 549) next to Sidi Djillali daira.
- Dairas of Bou Saâda match the executive decree published in JO n° 52 (July 2026); dairas of the other ten new wilayas match their Arabic Wikipedia pages. The full text of that decree could not be found.
- 1505 of the 1541 communes have a main postal code. The other 36 are `null` because the source lists no post office for them.
- Postal codes keep the 1984 wilaya prefix: communes of Bou Saâda (68) still use `28xxx`.
- Coordinates come from Wikidata (CC0), kossa/algerian-cities and geoalgeria (both MIT). 1518 communes are confirmed by two sources within 10 km, 9 were fixed by hand (mostly homonyms placed in another wilaya) and 14 come from a single source checked against the rest of their daira. They mark the approximate centre of each commune. See [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
- `data/*.json` is generated by `python tools/build.py`.

---

## الاختبارات

```bash
composer install
composer test
```

## الترخيص

MIT
