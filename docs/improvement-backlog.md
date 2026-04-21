# Improvement Backlog

Daftar prioritas perbaikan setelah refactor scope dan stabilisasi mobile toast.

## Prioritas Tinggi

- [x] Konsolidasi authorization ke `Policy` / `FormRequest::authorize()` agar tidak tersebar di controller dengan `abort(403)`.
- Tambah CI pipeline minimal untuk `php artisan test`, `npm run build`, dan `./vendor/bin/pint --test`.
- Perkuat validasi domain bisnis di backend untuk memastikan entitas nonaktif (`warehouse`/`product` dengan `is_active=false`) tidak bisa dipakai pada operasi write.

## Prioritas Sedang

- Tambah index database untuk query berat:
  - `inventory_logs (warehouse_id, created_at)`
  - `stock_entries (warehouse_id, product_id)`
  - `stock_transfers (destination_warehouse_id, status)`
  - `transfer_requests (to_warehouse_id, status)`
- Hardening produksi:
  - pastikan queue worker aktif dan termonitor
  - tambahkan security headers (CSP, HSTS, X-Frame-Options)
  - verifikasi `APP_DEBUG=false` di production
- Frontend quality gate:
  - pasang ESLint
  - aktifkan test frontend (Vitest) untuk flow kritis

## Prioritas Rendah

- Perbarui dokumentasi (`README`) agar sinkron dengan stack, praktik deploy, dan workflow QA saat ini.

