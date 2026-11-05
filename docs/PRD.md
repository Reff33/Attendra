# Attendra — Activity and Attendance Management System (PRD)

| Perkara | Butiran |
| --- | --- |
| Versi | 1.0 |
| Tarikh | 5 November 2026 |
| Status | Draf untuk semakan produk sebelum pembangunan |
| Nama produk | Attendra — nama kerja, belum dimuktamadkan |
| Platform | Aplikasi web responsif |
| Pengguna utama | Administrator, Staff dan peserta/student |
| Bahasa dokumen | Bahasa Melayu dengan istilah teknikal English |

## 1. Ringkasan produk

Attendra ialah sistem berasaskan web untuk mengurus aktiviti, senarai peserta dan rekod kehadiran. Admin atau staff boleh mencipta aktiviti, menetapkan tarikh serta masa kehadiran, mengimport senarai peserta sendiri, dan menjana pautan borang untuk dikongsi kepada student.

Peserta membuka pautan melalui telefon, mencari nama daripada senarai yang telah disediakan, mengesahkan pilihan dan menghantar kehadiran. Rekod disimpan dalam sistem dan boleh dilihat serta dieksport oleh pegawai yang mempunyai kebenaran.

Nilai utama produk ialah hubungan terus antara **aktiviti → senarai peserta → sesi kehadiran → laporan**. Staff tidak perlu membina semula borang dan menyemak nama secara manual untuk setiap aktiviti.

## 2. Keperluan asal dan andaian

### 2.1 Keperluan yang dinyatakan oleh pemilik projek

- Terdapat role Admin dan Staff.
- Admin atau staff boleh mencipta projek/aktiviti.
- Kehadiran diambil berdasarkan tarikh dan masa aktiviti.
- Staff boleh menjana pautan borang untuk student.
- Setiap aktiviti boleh menggunakan senarai peserta yang dimuat naik sendiri.
- Peserta boleh memilih nama yang telah tersedia.
- Semua rekod kehadiran disimpan dalam sistem.

### 2.2 Andaian cadangan dalam PRD ini

| ID | Andaian cadangan |
|---|---|
| A01 | MVP digunakan oleh satu organisasi dengan beberapa akaun staff. |
| A02 | Student tidak perlu akaun atau login untuk menghantar kehadiran. |
| A03 | “Upload database” bermaksud import senarai peserta daripada CSV atau Excel (.xlsx), bukan fail SQL atau sambungan ke database luar. |
| A04 | Satu aktiviti boleh mempunyai beberapa sesi kehadiran, contohnya pagi dan petang. |
| A05 | Staff hanya boleh mengurus aktiviti dan senarai peserta miliknya; admin boleh mengurus semua. |
| A06 | Senarai peserta dibekukan selepas aktiviti diterbitkan bagi mengekalkan ketepatan laporan (kecuali pendaftaran manual / walk-in oleh staff jika dibenarkan). |
| A07 | Zon masa paparan lalai ialah Asia/Kuala_Lumpur; masa pelayan menentukan kesahan penghantaran. |
| A08 | UI MVP menggunakan Bahasa Melayu/English (diselaraskan dalam pengurusan bahasa). |

## 3. Masalah yang hendak diselesaikan

1. Nama yang ditaip sendiri menghasilkan variasi ejaan dan rekod pendua.
2. Borang dan spreadsheet berasingan menyukarkan semakan peserta yang belum hadir.
3. Staff perlu mengulangi persediaan senarai peserta bagi setiap aktiviti.
4. Borang yang tidak mengikut jadual membolehkan kehadiran dihantar di luar waktu yang ditetapkan.
5. Rekod aktiviti dan kehadiran sukar dikumpulkan untuk laporan.

## 4. Objektif dan ukuran kejayaan

| Objektif | Ukuran / sasaran |
|---|---|
| Persediaan aktiviti mudah | Staff boleh mencipta aktiviti dengan satu sesi dan 100 peserta daripada fail yang sah dalam ≤5 minit selepas memahami fungsi asas. |
| Kehadiran cepat | Peserta boleh menyelesaikan borang dalam ≤30 saat dalam ujian kegunaan biasa. |
| Tiada rekod pendua | Tiada lebih daripada satu rekod aktif bagi kombinasi peserta dan sesi, termasuk penghantaran serentak. |
| Laporan tepat | Bilangan hadir, belum hadir dan jumlah peserta sepadan dengan roster serta rekod sesi. |
| Import boleh dipercayai | Semua baris tidak sah ditunjukkan bersama sebab sebelum import disahkan. |
| Pengasingan akses | Semua ujian akses silang antara staff ditolak, termasuk melalui URL dan permintaan API terus. |

## 5. Pengguna dan kebenaran

### 5.1 Persona

- **Administrator:** mengurus akaun, menyelia aktiviti organisasi dan menyemak laporan keseluruhan.
- **Staff/Penganjur:** mengimport peserta, menyediakan aktiviti, berkongsi pautan dan menyemak kehadiran.
- **Peserta/Student:** membuka pautan dan merekod kehadiran sendiri.

### 5.2 Matriks akses

| Fungsi | Admin | Staff | Peserta |
|---|---|---|---|
| Urus akaun staff dan role | Ya | Tidak | Tidak |
| Cipta aktiviti | Ya | Ya | Tidak |
| Lihat/ubah aktiviti | Semua | Milik sendiri | Maklumat asas melalui pautan sah |
| Import dan lihat senarai peserta penuh | Semua | Milik sendiri | Tidak |
| Cari pilihan nama dalam borang | Melalui pautan sah | Melalui pautan sah | Ya, dalam sesi yang dibenarkan |
| Tetapkan sesi dan pautan | Semua | Milik sendiri | Tidak |
| Hantar kehadiran sendiri | Melalui borang awam | Melalui borang awam | Ya |
| Semak laporan dan eksport | Semua | Milik sendiri | Tidak |
| Pembetulan rekod manual | Semua | Milik sendiri | Tidak |
| Lihat audit log | Semua | Perubahan aktiviti sendiri | Tidak |
| Penukaran Mod (Admin Console ↔ Organizer Mode) | Ya (Tanpa sekatan isolasi) | Tidak | Tidak |
| Operasi Pantas Staf (Imbas, Walk-In, Roster) | Ya (Merentas semua penganjur) | Milik sendiri | Tidak |

> **Catatan Kuasa Pentadbir (Admin Super-User Privilege):**
> Administrator memiliki hak penuh untuk menjalankan semua fungsi staf pada bila-bila masa. Admin boleh bertukar ke **Organizer Mode** secara terus melalui togol pantas di Dashboard Admin bagi mengurus aktiviti mana-mana staf tanpa sekatan isolasi aktiviti (PRD §5.2). Butang pintas operasi staf (`+ Session`, `📷 Scan QR`, `✍ Walk-In`, `📊 Reports`) disediakan terus dalam konsol utama Admin.

## 6. Skop produk

### 6.1 MVP — wajib untuk keluaran pertama

- Login admin/staff, logout, tukar kata laluan dan nyahaktif akaun.
- Admin mencipta akaun staff; tiada pendaftaran akaun awam.
- Dashboard aktiviti dan ringkasan kehadiran.
- Cipta, sunting, terbit dan arkib aktiviti.
- Import CSV/.xlsx, pemetaan kolum, preview dan validasi.
- Senarai peserta yang berasingan bagi setiap aktiviti.
- Beberapa sesi kehadiran dalam satu aktiviti.
- Pautan unik dan QR statik untuk membuka borang setiap sesi.
- Borang mudah alih dengan carian peserta dan pilihan nama (disertai pengesahan nombor matrik/ID).
- Kawalan masa, pencegahan rekod pendua dan pengesahan penghantaran.
- Rekod/pembetulan manual dengan sebab dan audit log.
- Laporan per sesi dan eksport CSV.

### 6.2 Cadangan penambahbaikan MVP

1. **Walk-in Participant:** Benarkan penganjur menambah peserta manual semasa sesi berlangsung.
2. **Rate Limiting & Throttle:** Sekat carian pantas / scraping senarai nama (maksimum 20 request/minit per IP).
3. **Pengesahan 4 Digit ID:** Wajibkan semakan 4 digit akhir nombor ID sebelum check-in untuk halang penipuan kehadiran kawan.
4. **Streaming / Batch Import:** Guna batch processing untuk fail roster 5,000 baris bagi elak memory limit server PHP.
5. **Pengesahan Geofence Lokasi (GPS Radius):** Semak lokasi fizikal peserta dalam radius 50m/100m/200m dari koordinat dewan aktiviti bagi membanteras kehadiran proksi secara mutlak.

## 8. Sistem Reka Bentuk UI & Tema Warna (Warm Organic Theme)

Menggantikan gaya generik/neon dengan tema mesra manusia (Human Warm & Organic), berasaskan templat rujukan mobile habit/home.

### 8.1 Palet Warna & Token Visual

| Peranan Token | Kod Hex | Kegunaan |
|---|---|---|
| Background Canvas | `#FAF7F2` | Latar belakang skrin dan halaman awam |
| Surface Card | `#FFFFFF` | Kad utama, borang input, floating nav bar |
| Primary Accent | `#F8A926` | Butang tindakan utama, active tab, penunjuk status aktif |
| Text Primary | `#1C1C1E` | Tajuk utama, label tebal, teks input |
| Text Secondary | `#8E8E93` | Subtitle, placeholder, label kecil |
| Status Live / Berjaya | `#EBF5EC` (bg) / `#2E7D32` (text) | Badge sesi aktif, pengesahan lulus, rekod hadir |
| Pastel Coral | `#FDE8E1` (bg) / `#E07A5F` (text) | Kad Scan QR Pass, avatar AR, butang bahaya/logout |
| Pastel Lavender | `#E8EAFA` (bg) / `#4C5270` (text) | Kad Import Roster, avatar MI, status Upcoming |
| Pastel Mint | `#E5F0E6` (bg) / `#3B5B3E` (text) | Kad Audit Log, avatar NS/VIP |
| Warm Gold | `#FEEFD0` / `#FDD97A` | Kad pengenalan Attendra, banner sesi aktif |

### 8.2 Tipografi & Radius Bentuk

- **Font Family**: `Inter` (Regular, Medium, Bold).
- **Corner Radius**:
  - Rangka Skrin Mudah Alih: `44px` (Gaya peranti moden).
  - Kad Utama & Banner: `24px` – `28px`.
  - Kad Peserta & Sub-kad: `20px`.
  - Butang Tindakan & Medan Input: `12px` – `14px` (Butang pil: `20px` – `26px`).
  - Floating Bottom Nav: `35px` (Pil melayang dengan bayang `0 6px 20px rgba(0,0,0,0.06)`).

### 8.3 Struktur 21 Skrin Mudah Alih & Aliran Lengkap

1. **Log Masuk (Mobile Login)**: Pilihan tab Staff/Admin, input emel dan kata laluan, kad makluman khas peserta (akses tanpa login). Tab Admin berpaut terus ke konsol pengurusan penuh.
2. **Dashboard Penganjur (Organizer Home)**: Kad pengenalan Attendra (`#FDD97A`), kad sesi langsung dengan bar progres, operasi pantas, navigasi pil bawah (English default).
3. **Senarai Sesi (Sessions & History)**: Penapis status (Semua, Live, Akan Datang, Selesai), kad sesi berserta pautan Share QR dan eksport.
4. **Cipta Sesi Baharu (+)**: Borang muat naik banner, tajuk, dewan/lokasi, masa buka/tutup, pemilihan dataset roster, konfigurasi **Attendance Verification** (Option A: 4-digit IC, Option B: Location GPS, Option C: IC + GPS), radius geofence (50m/100m/200m), dan kad ringkasan pra-terbit (Pre-publish summary).
5. **Perkongsian QR (Session QR & Share)**: Paparan kod QR besar, butang salin URL, muat turun poster PDF, dan pautan ke paparan peserta.
6. **Pengurusan Roster (Roster & Datasets)**: Import fail CSV/XLSX (penstriman Fast-Excel 5k), semakan duplikasi automatik, dan arkib senarai pelajar.
7. **Profil Pengguna (Profile & Settings)**: Maklumat staf/admin, pilihan tetapan bahasa antaramuka (Language Preferences: English Default vs Bahasa Melayu), borang tukar kata laluan, kekangan zon masa `Asia/Kuala_Lumpur`, dan butang log keluar.
8. **Kehadiran Awam (Public Attendance Roster)**: Pilihan bahasa awam (`[ 🇬🇧 EN | BM ]`), header status sesi, carian nama/matrik masa nyata, penapis chip, dan senarai nama peserta.
9. **Pengesahan 4-Digit & Pas Digital**: Pilihan bahasa awam berterusan, kotak input 4 angka akhir IC/matrik, butang pengesahan kehadiran, dan pas kehadiran digital berciri token hash kalis-duplikasi.
10. **Pengesahan Geofence Lokasi Peserta (Location Check)**: Radar visual bulatan zon geofence dewan kuliah, pengesanan koordinat GPS masa nyata, penunjuk ketepatan (±3.8m), dan status lulus dalam zon.
11. **Ralat Pengesahan & Percubaan Semula (Failure & Retry)**: Paparan amaran di luar zon geofence (jarak vs had dewan), tab status (Outside Radius, Wrong IC, Session Closed), dan pilihan mohon bantuan staf.
12. **Pengimbas Pas QR Staf (Staff QR Scanner)**: Viewfinder kamera langsung gelap dengan garisan laser imbasan, lampu suluh/tukar kamera, semakan token kriptografi tunggal, dan kelulusan masuk.
13. **Wizard Import Roster (Roster Import Wizard)**: Pemetaan kolum pintar (Nama, Matrik, No KP, Kumpulan), sokongan penstriman 5,000 baris Fast-Excel, dan laporan integriti sifar ralat.
14. **Audit Trail & Sejarah Keselamatan (Audit Log)**: Log kekal kriptografik (immutable), rekod pembetulan kehadiran manual oleh staf dengan alasan bertulis, dan sejarah pertukaran polisi sesi.
15. **Hab Operasi Pantas (Quick Operations Hub)**: Grid utiliti pantas staf merangkumi imbasan pas, import roster, audit trail, walk-in manual, muat turun matriks laporan, dan pembaharuan token.
16. **Pendaftaran Manual Walk-In (Manual Walk-In Check-In)**: Pemilih sesi aktif, mod carian vs pendaftaran baharu, semakan masa nyata integriti kekangan unik DB, pilihan wajib sebab override untuk audit (PRD §6.2), ringkasan audit hash SHA-256 dan pengesahan kehadiran.
17. **Matriks Kehadiran & Laporan (Attendance Matrix & Reports)**: Grid pelbagai sesi berserta kolum peserta beku (frozen name & matric), penunjuk status masa nyata (Present, Late, Absent, Walk-In, Pending), ringkasan KPI kadar kehadiran, dan butang eksport CSV/XLSX berverifikasi kebenaran.
18. **Konsol Pentadbir Sistem (Admin Overview & Analytics)**: Skrin utama Administrator dengan lencana `👑 ADMIN`, kad metrik sistem makro (Total Staff, Events Across All Faculties, Total Checked, 0 Breaches), butang tindakan pantas, dan kad audit integriti enjin database MariaDB (ACID transactions, Fast-Excel, kekangan unik).
19. **Direktori & Pengurusan Staf (Admin Staff Directory & Management)**: Direktori staf merentas fakulti (FTMK, FKE, FKM) dengan penapis carian, status akaun (Active / Suspended), butang `+ Add Staff`, kad polisi hak akses terasing, dan butang muat turun fail log akses staf (CSV).
20. **Borang Tambah Staf Baharu (Admin Add New Staff Form)**: Borang pendaftaran staf penganjur universiti dengan validasi format ID Staf unik (STF-xxx), emel rasmi UTeM, dropdown fakulti, janaan kata laluan sementara, pemilih tahap kebenaran (`● Staff Organizer` vs `○ System Co-Admin`), makluman isolasi aktiviti, dan suis hantaran kredensial emel automatik.
21. **Penyeliaan Aktiviti Merentas Organisasi (Admin All Activities Oversight)**: Pusat kawalan menyeluruh admin untuk semua aktiviti fakulti dengan penapis status (All, Live, Upcoming, Closed), bar kemajuan kehadiran masa nyata, maklumat penganjur/dewan, dan butang penguatkuasaan pentadbir (`Force Close ✕`, `🔄 Reassign Host`, `🔒 Audit Trail`, `📊 View Matrix`, `📥 Master CSV`).

