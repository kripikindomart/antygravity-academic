# Grading Module Implementation Plan

## Overview

Modul Penilaian (Grading) menangani proses setting bobot nilai, input nilai oleh dosen, dan kalkulasi nilai akhir mahasiswa.

## Database Schema

### 1. `skala_nilais` (Master Data)

Menyimpan aturan konversi nilai angka ke huruf.

-   `prodi_id`: FK ke ProgramStudi (Nullable). Jika Null = Default Universitas.
-   `huruf`: A, A-, B+, B... (String)
-   `bobot`: 4.0, 3.7... (Double)
-   `min_nilai`: 85.00 (Double)
-   `max_nilai`: 100.00 (Double)
-   `status_lulus`: Boolean

### 2. `komponen_nilais` (Setting per Mata Kuliah)

Menyimpan komponen penilaian untuk Kelas tertentu.

-   `kelas_matakuliah_id`: FK ke KelasMatakuliah.
-   `nama`: Tugas, UTS, UAS, Absensi (String).
-   `bobot`: Persentase (Double).
-   `is_active`: Boolean.

### 3. `nilai_mahasiswas` (Data Transaksi)

Menyimpan nilai mentah.

-   `komponen_nilai_id`: FK.
-   `mahasiswa_id`: FK.
-   `nilai`: Score (0-100).

### 4. `rekap_nilais` (Cache & Status)

Menyimpan hasil akhir dan status publikasi.

-   `kelas_matakuliah_id`: FK.
-   `mahasiswa_id`: FK.
-   `nilai_angka`: 87.5
-   `nilai_huruf`: A
-   `nilai_indeks`: 4.0
-   `status`: enum('draft', 'submitted', 'published').
-   `published_at`: Timestamp.
-   `published_by`: User ID (Staff Prodi).

## Implementasi Steps

1.  **Backend Structure**

    -   Migration & Models.
    -   Relationships (`KelasMatakuliah` hasMany `KomponenNilai`).

2.  **Master Data & Akademik UI**

    -   Page: `MasterData/SkalaNilai/Index.vue` (CRUD Skala).
    -   Page: `Akademik/KomponenNilai/Index.vue` (Setting Bobot per Kelas/MK).
    -   Fitur: Admin set template nilai (Tugas, UTS, UAS) dan bobotnya.

3.  **Dosen Interface**
    -   Page: `Kelas/Nilai/Index.vue`.
    -   **View Only**: Dosen melihat komponen yang sudah diset.
    -   **Input Nilai**: Spreadsheet view (Row: Mahasiswa, Col: Komponen).
    -   **Validasi**: Tidak bisa simpan jika total bobot komponen != 100%.
    -   Backend Service untuk kalkulasi `(nilai * bobot) / 100`.
    -   Konversi ke Huruf via `SkalaNilai`.
