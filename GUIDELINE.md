# Design Guideline — SIEDU : Sistem Evaluasi Dosen Terpadu
## Design System untuk Jurusan Teknologi Informasi

---

## 1. Arah Desain & Alasan

Produk ini bukan landing page pemasaran — ini adalah **instrumen kerja harian**: admin menginput data massal, dosen membaca banyak data agregat, mahasiswa mengisi form berulang tiap semester. Desain harus terasa seperti alat ukur yang presisi dan bisa dipercaya, bukan aplikasi konsumen yang playful.

**Konsep inti**: *"Evaluasi sebagai pembacaan instrumen (spec-sheet/datasheet), bukan rating konsumen."* Ini digali dari subjeknya sendiri — jurusan Teknologi Informasi/rekayasa, dengan kode kelas terstruktur (`MI2B`, `TRPL3A`), NIM, dan data tabular padat. Alih-alih bintang kuning generik ala e-commerce, rating ditampilkan seperti pembacaan meter/gauge teknis — memberi kesan objektif dan terukur, sesuai konteks jurusan IT.

Palet dan tipografi sengaja **menghindari tiga default umum desain AI**: bukan krem+serif+terracotta, bukan gelap+aksen neon, dan bukan broadsheet hairline hitam-putih. Sebagai gantinya: dasar putih-kebiruan yang tenang, aksen teal yang jarang dipakai sistem akademik Indonesia (biasanya biru tua/hijau kampus generik), dan tipografi teknis (Space Grotesk + IBM Plex) yang mencerminkan identitas jurusan IT tanpa jatuh ke estetika "hacker/terminal gelap" yang klise.

---

## 2. Token Warna

### 2.1 Palet Inti

| Nama Token | Hex | Peran |
|---|---|---|
| `color-ink` | `#16233A` | Teks utama, judul, navigasi (Navy institusional — tegas, dipercaya) |
| `color-canvas` | `#F5F7FA` | Latar belakang aplikasi (putih-kebiruan lembut, bukan krem) |
| `color-surface` | `#FFFFFF` | Kartu, panel, modal, baris tabel |
| `color-border` | `#DCE1E8` | Garis pembatas, divider, outline input (Slate Line) |
| `color-accent` | `#0E7C86` | Aksen utama/interaktif — tombol primer, link aktif, fokus (Signal Teal) |
| `color-rating` | `#E4A73B` | Warna khusus untuk elemen rating/gauge (Datasheet Amber) — **sengaja beda dari accent** supaya rating terasa seperti "data", bukan "chrome UI" |

### 2.2 Warna Semantik (Status)

| Nama Token | Hex | Peran |
|---|---|---|
| `color-success` | `#3F8F5F` | Status "Aktif", evaluasi terkirim, konfirmasi berhasil |
| `color-warning` | `#C97A2B` | Status "Cuti", peringatan non-kritis |
| `color-danger` | `#C1503F` | Status "DO", error, aksi destruktif |
| `color-muted` | `#6B7688` | Teks sekunder, placeholder, label pembantu |
| `color-accent-soft` | `#E4F3F3` | Latar tint untuk state aktif/hover ringan pada elemen ber-accent |

### 2.3 Prinsip Pemakaian Warna

- **Accent teal** hanya untuk elemen yang bisa diklik/interaktif (tombol, link, tab aktif, item nav aktif). Jangan pakai accent untuk dekorasi.
- **Amber rating** khusus untuk komponen penilaian bintang/gauge. Tidak dipakai di tempat lain — begitu pengguna melihat warna ini, otomatis tahu itu terkait skor.
- Warna status (success/warning/danger) hanya untuk **badge status** (aktif/cuti/DO) dan **notifikasi sistem** — jangan dipakai sebagai warna dekoratif section.
- Latar `canvas` dipakai di seluruh halaman; `surface` (putih solid) hanya untuk elemen yang "diangkat" di atas canvas (kartu, tabel, modal).

---

## 3. Tipografi

### 3.1 Peran Font

| Peran | Font | Alasan |
|---|---|---|
| **Display** | Space Grotesk | Judul halaman, angka besar di dashboard (skor rata-rata, jumlah responden). Geometris dan sedikit teknis — memberi karakter tanpa terkesan playful |
| **Body/UI** | IBM Plex Sans | Body text, label form, tombol, navigasi. Dirancang untuk konteks teknis/enterprise, nyaman dibaca di layar padat data |
| **Data/Mono** | IBM Plex Mono | NIM, kode kelas (`MI2B`, `TRPL3A`), kode mata kuliah, tanggal/timestamp, ID. Menegaskan bahwa data ini adalah "identifier terstruktur", bukan teks bebas |

Ketiganya dari keluarga desain yang selaras (IBM Plex Sans & Mono memang dirancang berpasangan), ditambah Space Grotesk sebagai aksen karakter di judul.

### 3.2 Skala Tipografi

| Token | Font | Ukuran / Line-height | Pemakaian |
|---|---|---|---|
| `text-display-xl` | Space Grotesk, 600 | 32px / 40px | Judul halaman utama (misal "Dashboard Evaluasi") |
| `text-display-l` | Space Grotesk, 600 | 24px / 32px | Judul section, angka skor besar |
| `text-heading` | IBM Plex Sans, 600 | 18px / 28px | Judul kartu, sub-section |
| `text-body` | IBM Plex Sans, 400 | 15px / 24px | Paragraf, deskripsi, isi form |
| `text-label` | IBM Plex Sans, 500 | 13px / 20px | Label form, nama kolom tabel |
| `text-small` | IBM Plex Sans, 400 | 12px / 18px | Caption, keterangan tambahan, timestamp |
| `text-mono-data` | IBM Plex Mono, 500 | 13px / 20px, tracking +0.02em | NIM, kode kelas, kode MK, ID |

**Aturan**: kode kelas dan NIM **selalu** dalam huruf kapital + mono, contoh: `MI2B`, `2401092013`. Ini konsisten di seluruh aplikasi (tabel, chip, dropdown) sebagai penanda visual "ini data identifier".

---

## 4. Layout & Struktur

### 4.1 Konsep Umum

- **Sidebar kiri tetap** (desktop) berisi navigasi berbasis role — isinya berbeda untuk Admin, Dosen, dan Mahasiswa.
- **Area konten** menggunakan grid card-based dengan max-width terkontrol (tidak edge-to-edge) supaya tabel data padat tetap nyaman dibaca.
- **Radius sudut moderat** (8px) — tidak sepenuhnya siku (kesan terlalu kaku/broadsheet), tidak juga bulat penuh (kesan aplikasi konsumen). Presisi teknis yang tetap ramah.
- **Border tipis 1px** (`color-border`) dipakai konsisten untuk memisahkan section, meniru garis grid pada spec-sheet/datasheet teknis — bukan dekorasi hairline broadsheet, tapi struktur fungsional tabel dan card.

### 4.2 Wireframe — Form Evaluasi (Mahasiswa)

```
┌─────────────────────────────────────────────────┐
│  [≡] Evaluasi Dosen — Ganjil 2025/2026     [👤]  │
├───────────┬─────────────────────────────────────┤
│ Sidebar   │  Pemrograman Web · Kelas MI2B        │
│           │  Dosen: Budi Santoso, S.Kom., M.Kom  │
│ • Daftar  │  ─────────────────────────────────   │
│   Evaluasi│                                       │
│ • Riwayat │  PENGUASAAN & PENYAMPAIAN MATERI      │
│           │  Bagaimana penilaian Anda terhadap    │
│           │  penguasaan dosen terhadap materi?    │
│           │  ⬥ ⬥ ⬥ ⬥ ⬥  (gauge rating, tap untuk│
│           │              isi 1–5)                 │
│           │                                       │
│           │  ... (pertanyaan berikutnya) ...      │
│           │                                       │
│           │  KESAN & SARAN                        │
│           │  ┌─────────────────────────────────┐  │
│           │  │ Apa yang paling Anda sukai?      │  │
│           │  └─────────────────────────────────┘  │
│           │  ┌─────────────────────────────────┐  │
│           │  │ Apa yang perlu diperbaiki?        │  │
│           │  └─────────────────────────────────┘  │
│           │                                       │
│           │           [ Kirim Evaluasi ]          │
└───────────┴─────────────────────────────────────┘
```

### 4.3 Wireframe — Dashboard Dosen

```
┌─────────────────────────────────────────────────┐
│  Hasil Evaluasi — Budi Santoso            [👤]   │
├───────────┬─────────────────────────────────────┤
│ Sidebar   │  Filter: [Kelas ▾] [Periode ▾] [Rating ▾]│
│           │  ─────────────────────────────────   │
│ • Ringkasan│  ┌───────────┐ ┌───────────┐        │
│ • Kesan &  │  │ Rata-rata │ │ Responden │         │
│   Saran    │  │  4.3 ⬥    │ │   28/30   │         │
│           │  └───────────┘ └───────────┘         │
│           │                                       │
│           │  SKOR PER KATEGORI                    │
│           │  Penguasaan Materi     ▓▓▓▓▓▓▓▓▓░ 4.5 │
│           │  Interaksi & Ketersediaan▓▓▓▓▓▓░░░ 3.8│
│           │  Kedisiplinan          ▓▓▓▓▓▓▓▓░░ 4.1 │
│           │                                       │
│           │  KESAN & SARAN (ANONIM)                │
│           │  ┌─────────────────────────────────┐  │
│           │  │ "Penjelasannya mudah dipahami"   │  │
│           │  │ ⭑⭑⭑⭑⭑ · Anonim                  │  │
│           │  └─────────────────────────────────┘  │
└───────────┴─────────────────────────────────────┘
```

### 4.4 Wireframe — Tabel Admin (Data Mahasiswa)

```
┌─────────────────────────────────────────────────┐
│  Data Mahasiswa                    [+ Tambah]    │
├─────────────────────────────────────────────────┤
│  [Cari NIM/nama]   [Prodi ▾] [Kelas ▾] [Status ▾]│
├──────────────┬──────────────┬───────┬───────────┤
│ NIM          │ Nama         │ Kelas │ Status     │
├──────────────┼──────────────┼───────┼───────────┤
│ 2401092013   │ Reyhan D. S. │ MI2B  │ ● Aktif    │
│ 2401092014   │ ...          │ MI2B  │ ○ Cuti     │
└──────────────┴──────────────┴───────┴───────────┘
```

---

## 5. Elemen Signature: Rating Gauge

Ini elemen paling khas dari desain ini — **pengganti bintang generik**.

**Konsep**: alih-alih 5 ikon bintang kuning standar (yang identik dengan Shopee/Google Maps), rating divisualisasikan sebagai **5 notch/tanda ukur** menyerupai pembacaan gauge pada alat instrumen teknis, terisi warna `color-rating` (amber) sesuai skor. Di sampingnya, skor numerik ditampilkan dalam `text-mono-data` — meniru cara datasheet menampilkan nilai pengukuran (misal "4.3 / 5.0").

```
Bentuk notch (bukan bintang):     ⬥ ⬥ ⬥ ⬥ ⬥
Terisi 3 dari 5:                  ⬥ ⬥ ⬥ ◇ ◇   4.3 / 5.0
```

- Saat interaktif (form pengisian mahasiswa): notch kosong berwarna `color-border`, notch terisi berwarna `color-rating`, dengan transisi hover 150ms.
- Saat display-only (dashboard dosen/admin): tambahkan bar horizontal tipis di bawah label kategori (lihat wireframe 4.3) sebagai representasi proporsional skor rata-rata — memberi kesan "meter", bukan "rating toko online".
- Gunakan bentuk **diamond/notch** (⬥) bukan bintang (★) di seluruh aplikasi untuk konsistensi identitas visual ini.

---

## 6. Komponen UI

### 6.1 Tombol

| Jenis | Gaya |
|---|---|
| Primer | Latar `color-accent`, teks putih, radius 8px, padding 10px 20px, hover: gelapkan 8% |
| Sekunder | Latar transparan, border 1px `color-border`, teks `color-ink`, hover: latar `color-accent-soft` |
| Destruktif | Border/teks `color-danger`, latar transparan, hover: latar merah muda pudar |
| Nonaktif | Latar `color-border`, teks `color-muted`, cursor not-allowed |

Label tombol memakai kata kerja aktif sesuai aksi: **"Simpan Evaluasi"**, **"Kirim Kesan & Saran"**, **"Tambah Mahasiswa"** — bukan "Submit"/"OK" generik.

### 6.2 Form

- Label di atas input (bukan di samping), memakai `text-label`.
- Border input 1px `color-border`, radius 6px, padding 10px 12px.
- Fokus: outline 2px `color-accent` dengan offset 2px (terlihat jelas untuk aksesibilitas keyboard).
- Pesan error tampil di bawah input, warna `color-danger`, dalam nada sistem yang lugas: *"NIM sudah terdaftar"*, bukan *"Maaf, terjadi kesalahan"*.

### 6.3 Tabel

- Header tabel: latar `color-canvas`, teks `text-label`, sticky saat scroll.
- Baris: pembatas 1px `color-border` (bukan zebra-striping — lebih tenang untuk data padat).
- Kolom berisi NIM/kode kelas/kode MK selalu pakai `text-mono-data`.
- Baris hover: latar `color-accent-soft` tipis.

### 6.4 Badge Status

Bentuk pill, latar tint 15% dari warna semantik, teks solid warna semantik:

| Status | Tampilan |
|---|---|
| Aktif | ● latar hijau muda, teks `color-success` |
| Cuti | ● latar oranye muda, teks `color-warning` |
| DO | ● latar merah muda, teks `color-danger` |
| Periode: Open | ● latar teal muda, teks `color-accent` |
| Periode: Closed | ● latar abu, teks `color-muted` |

### 6.5 Kartu Kesan & Saran (Anonim)

- Kartu `surface` dengan border 1px, radius 8px.
- Selalu sertakan badge kecil bertuliskan **"Anonim"** (latar abu muda, teks `color-muted`) di pojok kartu — penegasan visual bahwa identitas penulis memang sengaja disembunyikan, bukan hilang karena bug.
- Rating gauge kecil (lihat bagian 5) ditampilkan berdampingan dengan teks kesan untuk konteks skor.
- Kesan dan saran ditampilkan sebagai dua blok terpisah dengan label kecil "Kesan" / "Saran" — tidak digabung jadi satu paragraf.

### 6.6 Filter

Filter kelas/prodi/periode/rating ditampilkan sebagai **chip dropdown** sejajar horizontal di atas konten (lihat wireframe 4.3), bukan sidebar filter terpisah — supaya cepat diakses tanpa memakan ruang, sesuai kebutuhan dosen yang sering ganti-ganti kelas/periode.

### 6.7 Empty State

Saat data kosong (misal periode evaluasi belum dibuka, atau responden belum cukup untuk menampilkan kesan & saran karena threshold anonimitas belum terpenuhi):

> *"Kesan & saran akan tampil setelah minimal 5 mahasiswa mengisi evaluasi untuk kelas ini."*

Jelas, informatif, tidak menyalahkan pengguna, dan menjelaskan **kapan** kondisi akan berubah — bukan sekadar "Data tidak ditemukan".

---

## 7. Spacing & Grid

Skala spacing berbasis 4px:

| Token | Nilai |
|---|---|
| `space-1` | 4px |
| `space-2` | 8px |
| `space-3` | 12px |
| `space-4` | 16px |
| `space-6` | 24px |
| `space-8` | 32px |
| `space-12` | 48px |

Gunakan `space-4`–`space-6` sebagai jarak antar elemen form dan card. Gunakan `space-8`–`space-12` sebagai jarak antar section besar.

---

## 8. Motion

- Transisi state (hover, fokus, buka dropdown): **150–200ms ease-out**, tidak lebih.
- Tidak ada animasi dekoratif (parallax, efek masuk berlebihan) — ini alat kerja, bukan halaman promosi.
- Satu-satunya animasi yang boleh sedikit lebih terasa: **pengisian gauge rating** saat mahasiswa menekan skor (transisi warna notch dari kosong ke terisi, ~200ms), karena ini momen interaksi inti dari produk.
- Hormati `prefers-reduced-motion`: matikan semua transisi non-esensial jika diaktifkan pengguna.

---

## 9. Aksesibilitas

- Kontras teks minimal WCAG AA (4.5:1) — kombinasi `color-ink` di atas `color-canvas`/`color-surface` sudah memenuhi ini.
- Semua elemen interaktif (termasuk notch rating) harus bisa diakses via keyboard (Tab + Enter/Space untuk memilih skor).
- Target sentuh minimal 44×44px untuk semua tombol dan notch rating di tampilan mobile.
- Badge status jangan hanya mengandalkan warna — selalu sertakan label teks ("Aktif", "Cuti", "DO"), bukan warna bulat saja, untuk pengguna dengan buta warna.

---

## 10. Responsif

- **Desktop (≥1024px)**: sidebar tetap terbuka, layout 2 kolom (sidebar + konten).
- **Tablet (768–1023px)**: sidebar bisa di-collapse jadi ikon saja.
- **Mobile (<768px)**: sidebar berubah jadi bottom nav sederhana (khusus role Mahasiswa, karena mereka paling banyak akses via HP saat mengisi evaluasi). Form evaluasi didesain single-column penuh, gauge rating diperbesar agar mudah ditekan dengan jempol.

---

## 11. Ringkasan Token (Referensi Cepat)

```css
:root {
  /* Warna Inti */
  --color-ink: #16233A;
  --color-canvas: #F5F7FA;
  --color-surface: #FFFFFF;
  --color-border: #DCE1E8;
  --color-accent: #0E7C86;
  --color-accent-soft: #E4F3F3;
  --color-rating: #E4A73B;

  /* Warna Semantik */
  --color-success: #3F8F5F;
  --color-warning: #C97A2B;
  --color-danger: #C1503F;
  --color-muted: #6B7688;

  /* Radius */
  --radius-card: 8px;
  --radius-input: 6px;

  /* Spacing */
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-6: 24px;
  --space-8: 32px;
  --space-12: 48px;

  /* Font */
  --font-display: 'Space Grotesk', sans-serif;
  --font-body: 'IBM Plex Sans', sans-serif;
  --font-mono: 'IBM Plex Mono', monospace;
}
```

Font di atas tersedia gratis via Google Fonts — bisa langsung di-import di layout utama Laravel Blade (`<link>` Google Fonts atau install via npm untuk self-hosting).

---

## 12. Prinsip Penulisan (Copy) di UI

- Gunakan kata kerja aktif dan konsisten: tombol "Simpan Evaluasi" → notifikasi "Evaluasi Tersimpan" (bukan berubah jadi "Berhasil!" yang tidak konsisten).
- Sebut sesuatu dengan istilah yang dikenal pengguna, bukan istilah teknis sistem: mahasiswa melihat **"Isi Evaluasi"**, bukan **"Submit Formulir Kuesioner ID#123"**.
- Pesan error langsung ke inti masalah dan cara memperbaikinya: *"Semua pertanyaan wajib diberi nilai sebelum mengirim"* — bukan *"Terjadi kesalahan validasi"*.
- Untuk dosen/kaprodi, gunakan nada netral-informatif pada data sensitif: *"Kesan & saran ditampilkan tanpa identitas mahasiswa"*, bukan nada defensif atau berlebihan meyakinkan.