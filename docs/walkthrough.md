# 🚀 Walkthrough Perubahan — Production Readiness
> Tanggal: 16 April 2026

---

## Hasil Akhir Test Suite

```
 PASS  Tests\Unit\ExampleTest                         1 test
 PASS  Tests\Unit\Services\StockMovementServiceTest   11 tests
 PASS  Tests\Feature\AuthorizationTest                6 tests
 PASS  Tests\Feature\ExampleTest                      1 test
 PASS  Tests\Feature\StockTransferTest                5 tests
 PASS  Tests\Feature\TransferRequestTest              14 tests

Tests: 38 passed (104 assertions) — Duration: 1.36s
```

---

## Perubahan Detail

### 1. Pusher Debug Mode (`resources/js/bootstrap.js`)
WebSocket events tidak lagi bocor ke browser console di production.
```diff
- window.Pusher.logToConsole = true;
+ window.Pusher.logToConsole = import.meta.env.DEV;
```

---

### 2. Rate Limiting (`routes/web.php`)
Routes dipecah menjadi dua grup berdasarkan sifat operasinya.
- `throttle:60,1` → semua GET/read routes
- `throttle:20,1` → semua POST/PATCH/PUT/DELETE routes

---

### 3. Fix Carbon::parse() (`app/Http/Controllers/ReportController.php`)
Validasi format tanggal sebelum parsing untuk mencegah exception dari input arbitrary.
```diff
- $from = Carbon::parse($request->query('from'))->startOfDay();
+ $from = ($rawFrom && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawFrom))
+     ? Carbon::createFromFormat('Y-m-d', $rawFrom)->startOfDay()
+     : now()->subDays(30)->startOfDay();
```

---

### 4. Git Safety (`.gitignore`)
```diff
+ /database/*.sqlite
```

---

### 5. Session Encryption (`.env.example`)
```diff
- SESSION_ENCRYPT=false
+ SESSION_ENCRYPT=true
```

---

### 6. Query Ambiguity (`app/Http/Controllers/DashboardController.php`)
```diff
- ->whereColumn('quantity', '<', 'products.min_stock')
- ->where('warehouse_id', $user->warehouse_id)
+ ->whereColumn('stock_entries.quantity', '<', 'products.min_stock')
+ ->where('stock_entries.warehouse_id', $user->warehouse_id)
```

---

### 7. Trim Inertia Shared Data (`app/Http/Middleware/HandleInertiaRequests.php`)
```diff
- 'user' => $request->user(),
+ 'user' => $request->user()?->only(['id', 'name', 'email', 'role', 'warehouse_id', 'warehouse']),
+ 'flash' => ['success' => ..., 'error' => ...],
```

---

### 8. Duplicate Meta Tag (`resources/views/app.blade.php`)
Hapus satu dari dua `<meta name="apple-mobile-web-app-capable" content="yes">` yang duplikat.

---

### 9. Feature Test TransferRequestController
**File baru:** `tests/Feature/TransferRequestTest.php` — 14 test cases
**File baru:** `database/factories/TransferRequestFactory.php`
**Dimodifikasi:** `app/Models/TransferRequest.php` (tambah `use HasFactory`)
**Dihapus:** `tests/Feature/StockOutApprovalTest.php`
**Dimodifikasi:** `tests/Unit/Services/StockMovementServiceTest.php` (hapus blok usang)

---

### 10. Mobile UI Improvements
**Files:** `resources/css/app.css`, `resources/js/Pages/Dashboard.vue`, `resources/js/Pages/Stocks/Index.vue`

| Property | Desktop | Mobile (≤767px) |
|----------|---------|-----------------|
| `page-title` font-size | `1.75rem` | `1.375rem` |
| `dashboard__title` | `1.75rem` | `1.375rem` |
| `kpi-card__value` | `2rem` | `1.5rem` |
| `kpi-card` padding | `1.25rem` | `1rem 0.875rem` |
| `page-wrap` padding | `2rem 1.25rem` | `1.25rem 1rem` |
| Toast posisi | top-right | bottom (di atas nav) |
| `btn-ios` font-size | `0.875rem` | `0.8125rem` |

---

## Cara Menjalankan Test

```bash
/usr/local/Cellar/php@8.3/8.3.21/bin/php vendor/bin/pest --no-coverage
```

Atau dari Composer:
```bash
composer test
```
