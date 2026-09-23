-- =====================================================================
--  cleanup_duplicates.sql
--  Hapus data duplikat (berdasarkan ID + NO_URUT dalam satu UPLOAD_VERSION)
--  yang sudah tersimpan di database, agar saat PRINT tidak double.
--
--  Aturan duplikat = baris dengan kombinasi yang SAMA:
--     UPLOAD_VERSION + ID + NO_URUT
--  - Baris yang BERBEDA ID tapi NO_URUT sama = item yang BERBEDA, TETAP disimpan.
--  - Dari sekumpolin duplikat, yang disimpan = baris dengan AI (auto-increment)
--    terkecil (artinya data yang di-import pertama / paling awal).
--
--  !!! BACKUP DATABASE DULU sebelum menjalankan DELETE !!!
--  1. Jalankan bagian REVIEW dulu, periksa hasilnya.
--  2. Baru jalankan bagian DELETE di bawah (dalam transaksi, bisa ROLLBACK).
--  3. Setelah yakin, COMMIT;
-- =====================================================================

-- =====================================================================
--  BAGIAN A : REVIEW (hanya baca data, tdk mengubah apa-apa)
-- =====================================================================

-- A.1) Berapa banyak grup duplikat di tiap tabel (hanya untuk info)
SELECT 'data_label'        AS tbl, COUNT(*) AS dup_groups
FROM (SELECT UPLOAD_VERSION, ID, NO_URUT FROM data_label        GROUP BY UPLOAD_VERSION, ID, NO_URUT HAVING COUNT(*) > 1) x;
SELECT 'data_label_sp'     AS tbl, COUNT(*) AS dup_groups
FROM (SELECT UPLOAD_VERSION, ID, NO_URUT FROM data_label_sp     GROUP BY UPLOAD_VERSION, ID, NO_URUT HAVING COUNT(*) > 1) x;
SELECT 'data_label_sbsite' AS tbl, COUNT(*) AS dup_groups
FROM (SELECT UPLOAD_VERSION, ID, NO_URUT FROM data_label_sbsite GROUP BY UPLOAD_VERSION, ID, NO_URUT HAVING COUNT(*) > 1) x;
SELECT 'data_label_sl'     AS tbl, COUNT(*) AS dup_groups
FROM (SELECT UPLOAD_VERSION, ID, NO_URUT FROM data_label_sl     GROUP BY UPLOAD_VERSION, ID, NO_URUT HAVING COUNT(*) > 1) x;
SELECT 'data_label_tl'     AS tbl, COUNT(*) AS dup_groups
FROM (SELECT UPLOAD_VERSION, ID, NO_URUT FROM data_label_tl     GROUP BY UPLOAD_VERSION, ID, NO_URUT HAVING COUNT(*) > 1) x;
-- data_label_add: PRIMARY KEY (NO_URUT, UPLOAD_VERSION) -> tidak mungkin dobel, lewati.


-- A.2) Baris YANG AKAN DIHAPUS (berdasarkan AI > MIN(AI) per grup)
--   Salin tiap blok, periksa AI-nya, pastikan memang duplikat.

-- data_label
SELECT d.AI, d.UPLOAD_VERSION, d.ID, d.NO_URUT, d.PO
FROM data_label d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai
ORDER BY d.UPLOAD_VERSION, d.NO_URUT;

-- data_label_sp
SELECT d.AI, d.UPLOAD_VERSION, d.ID, d.NO_URUT, d.PO
FROM data_label_sp d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sp
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai
ORDER BY d.UPLOAD_VERSION, d.NO_URUT;

-- data_label_sbsite
SELECT d.AI, d.UPLOAD_VERSION, d.ID, d.NO_URUT, d.PO
FROM data_label_sbsite d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sbsite
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai
ORDER BY d.UPLOAD_VERSION, d.NO_URUT;

-- data_label_sl
SELECT d.AI, d.UPLOAD_VERSION, d.ID, d.NO_URUT, d.PO
FROM data_label_sl d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sl
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai
ORDER BY d.UPLOAD_VERSION, d.NO_URUT;

-- data_label_tl
SELECT d.AI, d.UPLOAD_VERSION, d.ID, d.NO_URUT, d.PO
FROM data_label_tl d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_tl
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai
ORDER BY d.UPLOAD_VERSION, d.NO_URUT;


-- =====================================================================
--  BAGIAN B : HAPUS DUPLIKAT  (dibungkus TRANSAKSI -> bisa ROLLBACK)
--  Jalankan hanya setelah bagian A review sudah OK.
--  InnoDB mendukung transaksi; rollback aman bila ada yang salah.
-- =====================================================================
START TRANSACTION;

-- data_label
DELETE d
FROM data_label d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai;

-- data_label_sp
DELETE d
FROM data_label_sp d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sp
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai;

-- data_label_sbsite
DELETE d
FROM data_label_sbsite d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sbsite
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai;

-- data_label_sl
DELETE d
FROM data_label_sl d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_sl
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai;

-- data_label_tl
DELETE d
FROM data_label_tl d
JOIN (
    SELECT MIN(AI) AS keep_ai, UPLOAD_VERSION, ID, NO_URUT
    FROM data_label_tl
    WHERE ID != '' AND NO_URUT IS NOT NULL
    GROUP BY UPLOAD_VERSION, ID, NO_URUT
    HAVING COUNT(*) > 1
) dup ON d.UPLOAD_VERSION = dup.UPLOAD_VERSION AND d.ID = dup.ID AND d.NO_URUT = dup.NO_URUT
WHERE d.AI > dup.keep_ai;

-- Setelah yakin semua benar:
COMMIT;
-- Jika ingin membatalkan:  ROLLBACK;


-- =====================================================================
--  BAGIAN C (OPSIONAL): cegah duplikat kembali terjadi di masa depan
--  -------------------------------------------------------------------
--  Akar penyebab duplikat = IMPORT pakai "INSERT IGNORE", tapi index
--  yang ada (uniq_version_nourut) hanya NON-UNIQUE sehingga INSERT
--  IGNORE justru TIDAK melindungi. Setelah DELETE+COMMIT di atas
--  selesai dan tiap (UPLOAD_VERSION, ID, NO_URUT) sudah unik, jalankan
--  BLOCK DI BAWAH (hapus tanda --) agar IMPORT berikutnya otomatis
--  lewati baris duplikat via INSERT IGNORE.
--
--  Catatan: index ini hanya mencegah duplikat pada kombinasi
--  (UPLOAD_VERSION, ID, NO_URUT) persis. Baris dengan NO_URUT sama
--  tapi ID BERBEDA tetap diperbolehkan (itu item yang berbeda).
--  Jalankan hanya 1x, setelah COMMIT di bagian B selesai.
-- =====================================================================
-- ALTER TABLE data_label        ADD CONSTRAINT uk_data_label         UNIQUE (UPLOAD_VERSION, ID, NO_URUT);
-- ALTER TABLE data_label_sp     ADD CONSTRAINT uk_data_label_sp     UNIQUE (UPLOAD_VERSION, ID, NO_URUT);
-- ALTER TABLE data_label_sbsite ADD CONSTRAINT uk_data_label_sbsite   UNIQUE (UPLOAD_VERSION, ID, NO_URUT);
-- ALTER TABLE data_label_sl     ADD CONSTRAINT uk_data_label_sl       UNIQUE (UPLOAD_VERSION, ID, NO_URUT);
-- ALTER TABLE data_label_tl     ADD CONSTRAINT uk_data_label_tl       UNIQUE (UPLOAD_VERSION, ID, NO_URUT);

