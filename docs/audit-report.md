# 🔍 Laporan Audit — Inventori IMS PWA

> **Audit Pertama:** 16 April 2026  
> **Re-Audit (Update):** 17 April 2026  
> **Test Suite Terakhir:** `38 passed, 0 failed (104 assertions)` — ✅ Hijau

---

## ✅ Verdict: SIAP PRODUKSI (dengan catatan ops)

Semua isu kritikal dari sisi kode sudah diselesaikan. Test suite tetap hijau.
Tersisa catatan pasca-launch dan satu temuan baru teknis (medium) dari re-audit ini.

---

## 🔴 KRITIKAL — SUDAH DISELESAIKAN

### ✅ 1. Pusher.logToConsole Bocor ke Production
**File:** `resources/js/bootstrap.js`

Seluruh event WebSocket (data stok, notifikasi, channel auth token) sebelumnya tercetak di browser console siapapun. Sekarang dikondisikan hanya aktif saat development.

```js
// Sebelum
window.Pusher.logToConsole = true;

// Sesudah
window.Pusher.logToConsole = import.meta.env.DEV;
```

---

### ✅ 2. Tidak Ada Rate Limiting
**File:** `routes/web.php`

Semua POST routes tanpa rate limit membuka celah spam dan stok flood. Sekarang dibagi dua grup:

| Grup | Limit | Routes |
|------|-------|--------|
| Read | 60 req/menit | Dashboard, listing, reports |
| Write | 20 req/menit | Stock in, stock out, transfers, CRUD |

---

### ✅ 3. Carbon::parse() Tanpa Validasi
**File:** `app/Http/Controllers/ReportController.php`

Input tanggal arbitrary (`"next monday + 999 years"`) bisa menyebabkan exception yang tidak ter-handle. Sekarang divalidasi dengan regex `^\d{4}-\d{2}-\d{2}$` sebelum parsing.

---

### ✅ 4. database.sqlite Ter-commit ke Git
**File:** `.gitignore`

`/database/*.sqlite` sudah ditambahkan ke `.gitignore`.

---

## ⚠️ PENTING — SUDAH DISELESAIKAN

### ✅ 5. N+1 Query / whereColumn Ambiguity di DashboardController
**File:** `app/Http/Controllers/DashboardController.php`

Kolom tanpa kualifikasi tabel bisa menyebabkan ambiguous column error. Sekarang eksplisit: `stock_entries.quantity`, `stock_entries.warehouse_id`.

---

### ✅ 6. SESSION_ENCRYPT=false di .env.example
**File:** `.env.example`

Diubah ke `SESSION_ENCRYPT=true` agar template production sudah aman by default.

---

### ✅ 7. HandleInertiaRequests Membocorkan Seluruh User Object
**File:** `app/Http/Middleware/HandleInertiaRequests.php`

Sebelumnya seluruh model User dikirim ke semua halaman JavaScript. Sekarang hanya field yang diperlukan: `id, name, email, role, warehouse_id, warehouse`. Flash message `success`/`error` juga ditambahkan ke shared data.

---

### ✅ 8. Duplicate Meta Tag di app.blade.php
**File:** `resources/views/app.blade.php`

`<meta name="apple-mobile-web-app-capable">` yang duplikat telah dihapus.

---

### ✅ 9. Verifikasi Icon PWA
**Direktori:** `public/icons/`

Semua 8 ukuran icon tersedia: 72×72, 96×96, 128×128, 144×144, 152×152, 192×192, 384×384, 512×512. ✅

---

### ✅ 10. Test Coverage TransferRequestController
**File baru:** `tests/Feature/TransferRequestTest.php`

14 test cases yang mencakup seluruh alur approval workflow:

| Skenario | Hasil |
|----------|-------|
| Branch Admin mengajukan request | ✅ 4 passed |
| Super Admin menyetujui + stok bergerak | ✅ 4 passed |
| Super Admin menolak request | ✅ 3 passed |
| Tampilan index per-role | ✅ 3 passed |

File tambahan: `database/factories/TransferRequestFactory.php`, `HasFactory` di model `TransferRequest`.

Test usang dihapus:
- `tests/Feature/StockOutApprovalTest.php` — test fitur approval yang sudah tidak ada
- Blok `requestStockOut()`/`approveStockOut()` di unit test

---

## 🆕 TEMUAN RE-AUDIT — 17 APRIL 2026

### 🟡 11. DATE_FORMAT MySQL-Only Syntax di ReportController
**File:** `app/Http/Controllers/ReportController.php` — baris 107  
**Tingkat:** Medium | **Prioritas:** Post-Launch

```php
// Saat ini (MySQL-only):
$availableMonths = DB::table('inventory_logs')
    ->select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'))
    ->groupBy('month')
    ->orderBy('month', 'desc')
    ->pluck('month');
```

`DATE_FORMAT()` adalah fungsi MySQL/MariaDB dan **tidak kompatibel dengan SQLite**. Test suite berjalan dengan SQLite, namun tes yang ada tidak menyentuh `ReportController`, sehingga masalah ini tidak terdeteksi oleh tes.

**Risiko:** Jika ada tes yang ditambahkan untuk Reports di masa depan, query ini akan crash di SQLite. Di production MySQL ini berfungsi dengan baik.

**Rekomendasi (post-launch):** Ganti dengan fungsi portable:
```php
->select(DB::raw("strftime('%Y-%m', created_at) as month")) // SQLite
// atau buat helper yang detect driver
```

---

### 🟡 12. StockEntry::all() Unbounded di Tiga Controller
**File:** `StockTransferController`, `StockOutController`, `TransferRequestController`  
**Tingkat:** Medium | **Prioritas:** Post-Launch

Ketiga controller ini mengirim **semua** StockEntry ke frontend untuk kebutuhan validasi form (cek stok sebelum input):

```php
'stocks' => StockEntry::all(['warehouse_id', 'product_id', 'quantity']),
```

Dengan ratusan produk × puluhan gudang, payload Inertia bisa membengkak signifikan. Saat ini (data kecil) tidak menjadi masalah.

**Rekomendasi (post-launch):** Scope ke warehouse pengguna atau muat via AJAX on-demand.

---

### 🟡 13. StockController Global View — selectRaw + eager loading conflict
**File:** `app/Http/Controllers/StockController.php` — baris 40-43  
**Tingkat:** Medium (potential bug) | **Prioritas:** Post-Launch

```php
if ($isGlobal) {
    $query->selectRaw('MIN(id) as id, product_id, SUM(quantity) as quantity')
          ->groupBy('product_id');
    $stocks = $query->paginate(15)->withQueryString();
}
```

`$query` sudah memiliki `->with(['product.category', 'warehouse'])` dari baris 21. Ketika `selectRaw` mengganti select columns, relasi `warehouse` bisa menghasilkan data tidak terduga karena `warehouse_id` tidak ada dalam select. **Relasi product mungkin masih bekerja** karena `product_id` ada, tapi `warehouse` akan menghasilkan `null`.

**Rekomendasi:** Gunakan query terpisah untuk global view atau hapus `with('warehouse')` pada mode global.

---

### 🟢 14. StockController::store() Exception Catch Terlalu Lebar
**File:** `app/Http/Controllers/StockController.php` — baris 94  
**Tingkat:** Low | **Prioritas:** Nice-to-Have

```php
// Saat ini:
} catch (\Exception $e) {

// Lebih tepat:
} catch (\RuntimeException $e) {
```

`\Exception` menangkap semua exception (termasuk `\TypeError`, `\PDOException`, dll.) dan meneruskannya sebagai pesan error ke pengguna. Ini bisa membocorkan pesan error teknis internal. `StockMovementService` hanya melempar `\RuntimeException`, jadi `catch (\RuntimeException $e)` sudah cukup dan lebih eksplisit.

---

### 🟢 15. Inkonsistensi Role Comparison di StockController
**File:** `app/Http/Controllers/StockController.php` — baris 18, 22, 28  
**Tingkat:** Low | **Prioritas:** Nice-to-Have

```php
// Di StockController (string comparison):
$user->role->value === 'super_admin'

// Di User model tersedia helper (sudah digunakan di controller lain):
$user->isSuperAdmin()
$user->isBranchAdmin()
```

Inkonsistensi minor — secara fungsional sama, tapi string comparison rentan typo dan tidak memanfaatkan helper yang sudah ada.

---

## 🎨 PENILAIAN UI/UX

### Nilai Keseluruhan: **8.7 / 10** _(naik dari 8.5)_

#### Perbaikan yang Dilakukan Sejak Audit Pertama:
| Commit | Perbaikan |
|--------|-----------|
| `ccb8e98` | Toast notification tidak lagi stretch full-width di mobile |
| `51fee2f` | Font size title dan KPI cards diperkecil untuk mobile; card stretch ke edge layar |
| Audit pertama | Toast dipindah ke bawah layar, di atas MobileNav |

#### Yang Masih Bisa Diperbaiki (Post-Launch):
| Masalah | Prioritas |
|---------|-----------|
| KPI "Pending Approvals" selalu 0 (query ke StockOutStatus::Pending, tapi workflow sudah instant) | Medium |
| Bahasa campur Indonesia/Inggris di UI | Low |
| Nama aplikasi tidak konsisten (Inventori IMS vs IMS PWA) | Low |
| Grid 4 kolom sheet terlalu padat di layar 320px | Low |

---

## 🏛️ BEST PRACTICES ASSESSMENT

### ✅ Sudah Bagus
- `StockMovementService` — atomic transactions, `lockForUpdate()`, race-condition proof
- Enums digunakan konsisten (`Role`, `TransferStatus`, `StockOutCategory`, `StockMovementType`)
- Policies (`StockTransferPolicy`, `WarehousePolicy`) tepat sasaran dan tested
- Form Requests terpisah dari controller (`StoreStockOutRequest`, `StoreStockTransferRequest`)
- Broadcasting channels dengan auth guard yang benar
- `withQueryString()` di paginator — filter terjaga saat pindah halaman
- `Pusher.logToConsole` hanya aktif di DEV ✅
- Rate limiting write/read terpisah ✅
- Inertia shared data hanya expose field minimum ✅

### ⚠️ Masih Perlu Perhatian (Post-Launch)
| Masalah | Controller | Rekomendasi |
|---------|------------|-------------|
| Authorization tidak konsisten | `TransferRequestController` | Buat `TransferRequestPolicy`, ganti `abort(403)` inline |
| Authorization tidak konsisten | `StockOutController` | Pindahkan role-check ke Policy |
| Authorization tidak konsisten | `StockController` | Gunakan helper `isSuperAdmin()`, pertimbangkan Policy |
| DATE_FORMAT MySQL-only | `ReportController` | Gunakan DB-agnostic syntax untuk portabilitas tes |
| StockEntry::all() tidak dibatasi | 3 controller | Scope per-warehouse atau muat via API |

---

## 📋 CHECKLIST DEPLOY PRODUCTION

### ✅ Sudah Selesai (Kode)
- [x] Kondisikan Pusher.logToConsole
- [x] Rate limiting di semua write routes
- [x] Validasi input tanggal Carbon
- [x] database.sqlite di .gitignore
- [x] SESSION_ENCRYPT=true di env.example
- [x] Fix query ambiguity DashboardController
- [x] Trim Inertia shared data
- [x] Hapus duplicate meta tag
- [x] Verifikasi icon PWA
- [x] Test TransferRequestController (14 test cases)
- [x] Fix toast width di mobile
- [x] Optimize mobile font sizes & layout

### 🔧 Tanggung Jawab Tim Ops (env production)
- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL=https://domain-anda.com`
- [ ] DB_PASSWORD kuat
- [ ] Pusher keys baru (jika perlu rotate)
- [ ] `SESSION_ENCRYPT=true` di `.env` production
- [ ] `php artisan optimize` + `php artisan migrate --force`
- [ ] SSL aktif (wajib untuk Pusher WebSocket HTTPS)

---

## 📊 RINGKASAN PERUBAHAN FILE

### Audit Pertama (16 April 2026)
| File | Jenis Perubahan |
|------|----------------|
| `resources/js/bootstrap.js` | Fix Pusher debug mode |
| `routes/web.php` | Tambah rate limiting dua grup |
| `app/Http/Controllers/ReportController.php` | Fix Carbon::parse() |
| `.gitignore` | Tambah database/*.sqlite |
| `.env.example` | SESSION_ENCRYPT=true |
| `app/Http/Controllers/DashboardController.php` | Fix whereColumn ambiguity |
| `app/Http/Middleware/HandleInertiaRequests.php` | Trim shared data + flash |
| `resources/views/app.blade.php` | Hapus duplicate meta |
| `app/Models/TransferRequest.php` | Tambah HasFactory |
| `database/factories/TransferRequestFactory.php` | **FILE BARU** |
| `tests/Feature/TransferRequestTest.php` | **FILE BARU** — 14 test cases |
| `tests/Feature/StockOutApprovalTest.php` | **DIHAPUS** — fitur usang |
| `tests/Unit/Services/StockMovementServiceTest.php` | Hapus test usang |
| `resources/css/app.css` | Mobile responsive global |
| `resources/js/Pages/Dashboard.vue` | Mobile responsive |
| `resources/js/Pages/Stocks/Index.vue` | Mobile responsive |

### Re-Audit (17 April 2026) — Temuan Baru (belum diubah, dicatat untuk post-launch)
| Temuan | File | Status |
|--------|------|--------|
| DATE_FORMAT MySQL-only | `ReportController.php` | 🟡 Dicatat — Post-Launch |
| StockEntry::all() unbounded | 3 controller | 🟡 Dicatat — Post-Launch |
| selectRaw + eager loading conflict | `StockController.php` | 🟡 Dicatat — Post-Launch |
| Exception catch terlalu lebar | `StockController.php` | 🟢 Nice-to-Have |
| Role comparison string vs helper | `StockController.php` | 🟢 Nice-to-Have |

---

## 📈 RIWAYAT COMMIT PASCA-AUDIT

```
ccb8e98  UI: Fix toast component stretching to full width on mobile
51fee2f  UI: Optimize mobile layout for all pages (smaller fonts, stretch cards)
280f146  chore: complete production readiness audit (audit pertama)
```

---

## 🧪 HASIL TEST SUITE TERKINI

```
Tests:    38 passed (104 assertions)
Duration: 1.51s
Date:     17 April 2026

  PASS  Tests\Unit\ExampleTest                         1 test
  PASS  Tests\Unit\Services\StockMovementServiceTest  11 tests
  PASS  Tests\Feature\AuthorizationTest                6 tests
  PASS  Tests\Feature\ExampleTest                      1 test
  PASS  Tests\Feature\StockTransferTest                5 tests
  PASS  Tests\Feature\TransferRequestTest             14 tests
```

---

## 🚦 STATUS PRODUKSI

**Siap go-live** setelah tim ops menyelesaikan checklist environment di atas.

Arsitektur bersih, test suite hijau (38 passed, 104 assertions), keamanan lapisan kode sudah diamankan.  
Temuan baru dari re-audit ini bersifat teknis/optimasi — **tidak menghalangi go-live**.
