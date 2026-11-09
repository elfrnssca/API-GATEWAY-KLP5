# Mini LMS Terdistribusi - API Gateway

API Gateway merupakan pintu masuk utama (*single entry point*) untuk seluruh *request* dari *client* ke mikroservis Mini LMS Terdistribusi. Gateway bertugas mengarahkan rute *request*, memvalidasi ketersediaan servis, meneruskan token otorisasi, dan menangani kegagalan sistem (*timeout* dan *service unavailable*).

## Informasi Servis
- **Nama Servis:** API Gateway
- **Port:** 8101
- **Bahasa Pemrograman:** PHP (Native)
- **URL Dasar:** `http://127.0.0.1:8101`

## Anggota Kelompok
- Nama  : Khairi Athallah Dafi
- NIM   : 220209501051
- Nama  : Akmar Aqilah Kautsar
- NIM   : 220209501046
- Nama  : Fransisca Gabriela
- NIM   :240209501089
- Nama  : Athiyyah Najwa
- NIM   : 240209502023
- Prodi/Kelas:Pendidikan Teknik Informatika dan Komputer Kelas B Sistem Terdistribusi 



## Pemetaan Servis (`services.json`)
Alamat seluruh servis terdaftar dalam `services.json`:
- `auth`: `http://127.0.0.1:8102`
- `course`: `http://127.0.0.1:8103`
- `assignment`: `http://127.0.0.1:8104`
- `submission`: `http://127.0.0.1:8105`
- `notification`: `http://127.0.0.1:8106`
- `monitoring`: `http://127.0.0.1:8107`

## Cara Menjalankan Servis
1. Buka terminal di folder proyek ini.
2. Jalankan perintah server bawaan PHP:
   ```bash
   php -S 127.0.0.1:8101 index.php