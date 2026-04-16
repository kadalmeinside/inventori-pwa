# ✅ Task List — Inventori IMS PWA
> Terakhir diperbarui: 16 April 2026

---

## 🔴 KERJAKAN SEKARANG — SELESAI ✅

### Keamanan & Konfigurasi
- [x] Kondisikan `Pusher.logToConsole` hanya aktif saat development
- [x] Tambahkan rate limiting (throttle) ke semua POST routes — 60/menit read, 20/menit write
- [x] Fix `Carbon::parse()` tanpa validasi di `ReportController` → gunakan `Carbon::createFromFormat()`
- [x] Pastikan `database.sqlite` tidak ter-commit ke git (tambah ke `.gitignore`)
- [x] Set `SESSION_ENCRYPT=true` di `.env.example` (referensi prod)
- [x] Fix ambiguitas kolom `whereColumn` di `DashboardController` → kualifikasi nama tabel
- [x] Trim data yang dikirim ke Inertia shared data (`HandleInertiaRequests`) → only 6 fields + flash
- [x] Hapus duplicate `<meta name="apple-mobile-web-app-capable">` di `app.blade.php`
- [x] Verifikasi semua icon PWA ada di `public/icons/` → semua 8 ukuran tersedia ✅

### Testing
- [x] Buat feature test untuk `TransferRequestController` (14 test cases, semua passed)
- [x] Buat `TransferRequestFactory` untuk mendukung test
- [x] Tambah `HasFactory` trait ke model `TransferRequest`
- [x] Hapus test usang `StockOutApprovalTest` (test untuk fitur yang sudah dihapus)
- [x] Bersihkan unit tests `StockMovementServiceTest` dari `requestStockOut()`/`approveStockOut()` lama

### UI Mobile
- [x] Perkecil font size & elemen pada tampilan mobile di seluruh halaman
  - `.page-title` / `.dashboard__title`: 1.75rem → 1.375rem
  - `.kpi-card__value`: 2rem → 1.5rem
  - Padding halaman dikurangi pada mobile
  - Toast diposisikan ulang (bottom, di atas mobile nav)
  - Button font size dikurangi

---

## 🟢 NANTI — Post-Launch (Nice to Have)

- [ ] Normalize authorization ke Policy pattern (TransferRequest, StockOut, Stock saat ini masih pakai manual role-check)
- [ ] Fix KPI card "Pending Approvals" yang selalu 0 (ganti jadi "Stock Out Hari Ini")
- [ ] Konsistenkan bahasa ke **Bahasa Indonesia** saja (saat ini campur)
- [ ] Konsistenkan nama aplikasi ("Inventori" atau "Inventori IMS", pilih satu)
- [ ] Disable `devOptions` PWA di `vite.config.js` untuk production build

---

## ⚠️ DISELESAIKAN SEBELUM DEPLOY (oleh tim ops / pemilik env prod)

> Item di bawah ini sudah ada di env production, tidak perlu dikerjakan di dev:

- [~] Rotate Pusher app secret & keys (jika pernah bocor)
- [~] Set `APP_ENV=production`, `APP_DEBUG=false`
- [~] Ganti `DB_PASSWORD` ke password yang kuat
- [~] Set `APP_URL` ke domain production
