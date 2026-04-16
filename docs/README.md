# 📚 Dokumentasi — Inventori IMS PWA

Folder ini berisi dokumentasi internal sistem untuk keperluan tracking, audit, dan referensi developer.

---

## Daftar Dokumen

| File | Deskripsi |
|------|-----------|
| [audit-report.md](./audit-report.md) | Laporan audit production readiness lengkap — temuan, penilaian, dan status perbaikan |
| [task.md](./task.md) | Checklist task — yang sudah selesai dan yang masih harus dikerjakan |
| [walkthrough.md](./walkthrough.md) | Detail teknis semua perubahan kode yang dilakukan dalam sesi audit |

---

## Status Saat Ini

**Audit dilakukan:** 16 April 2026  
**Status:** ✅ Siap Production (*dengan penyelesaian checklist ops*)  
**Test Suite:** 38 passed, 0 failed (104 assertions)

---

## Quick Reference — Cara Jalankan Test

```bash
/usr/local/Cellar/php@8.3/8.3.21/bin/php vendor/bin/pest --no-coverage
```

---

## Checklist Ops Sebelum Deploy

> Hal-hal ini tidak bisa dikerjakan dari sisi kode — harus dikerjakan di server/env production:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL=https://domain-anda.com`
- [ ] `SESSION_ENCRYPT=true`
- [ ] DB_PASSWORD kuat
- [ ] SSL aktif (wajib untuk Pusher WebSocket)
- [ ] `php artisan optimize`
- [ ] `php artisan migrate --force`

Lihat detail lengkap di [audit-report.md](./audit-report.md#-checklist-deploy-production).
