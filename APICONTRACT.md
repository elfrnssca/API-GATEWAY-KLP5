---

### 2. File `API_CONTRACT.md`

# API Contract: API Gateway (Kelompok 5)

## Informasi umum
- Port: 8101
- URL dasar: http://127.0.0.1:8101
- Format data: JSON

## Endpoint 1: Health Check Gateway
- Metode dan URL: GET /health
- Autentikasi: Tidak
- Request body: -;
- Respons sukses: HTTP 200 OK
{
  "success": true,
  "data": {
    "service": "gateway",
    "status": "healthy"
  }
}
- Respons gagal: HTTP 404 Not Found (error.code: not_found, Endpoint tidak ditemukan)

## Endpoint 2: Daftar Service Terdaftar
- Metode dan URL: GET /api/services
- Autentikasi: Tidak
- Request body: -
- Respons sukses: HTTP 200 OK
{
  "success": true,
  "data": {
    "auth": "http://127.0.0.1:8102",
    "course": "http://127.0.0.1:8103",
    "assignment": "http://127.0.0.1:8104",
    "submission": "http://127.0.0.1:8105",
    "notification": "http://127.0.0.1:8106",
    "monitoring": "http://127.0.0.1:8107"
  }
}
- Respons gagal: HTTP 500 Internal Server Error (error.code : config_error, services.json tidak ditemukan)

## Endpoint 3: Forwarding Dynamic Request
- Metode dan URL: ANY /api/{service}/{path}
- Autentikasi: Mengikuti service tujuan (meneruskan header Authorization: Bearer <token>)
- Request body: Menyesuaikan request ke service tujuan (misal JSON pada POST /api/auth/login)
- Respons sukses: Memuat status HTTP asli (200/201) dan response JSON dari service tujuan
{
  "success": true,
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsIn..."
  }
}
- Respons gagal:
  - HTTP 404 Not Found (error.code: service_not_found, Service tujuan tidak terdaftar)
  - HTTP 503 Service Unavailable (error.code: service_unavailable, Service tujuan tidak dapat dihubungi/mati)
  - HTTP 504 Gateway Timeout (error.code: gateway_timeout, Service tujuan melewati batas waktu timeout)

## Dependensi ke service lain
- Authentication (port 8102): Meneruskan request login, registrasi, dan verifikasi token.
- Course (port 8103): Meneruskan request pengelolaan data mata kuliah.
- Assignment (port 8104): Meneruskan request pengelolaan data tugas.
- Submission File (port 8105): Meneruskan request pengumpulan tugas.
- Notification (port 8106): Meneruskan request pengelolaan notifikasi.
- Monitoring (port 8107): Meneruskan request pemantauan status kesehatan service.