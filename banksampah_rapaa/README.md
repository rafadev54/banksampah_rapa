# Bank Sampah Akbar — portal per peran

## Pemasangan
1. Salin folder ini ke `htdocs/banksampah_akbar/` (XAMPP), timpa file lama.
2. Buka phpMyAdmin, pilih database `banksampah_akbar`, tab SQL, jalankan isi `database_update.sql` (cukup sekali).
3. Buka `http://localhost/banksampah_akbar/` lalu login.

## Struktur
| Folder      | Untuk      | Halaman |
|-------------|------------|---------|
| `admin/`    | Admin      | dashboard, users, sampah, transaksi, tarik saldo, pembelian |
| `siswa/`    | Siswa      | beranda, tarik saldo, profil |
| `guru/`     | Guru       | beranda, tarik saldo, profil |
| `pengepul/` | Pengepul   | beranda (stok), pembelian, profil |
| `includes/` | Kode bersama (tidak bisa dibuka langsung dari browser) |

Login tetap satu (`login.php`); setelah masuk, pengguna diarahkan ke folder sesuai peran.
Membuka folder peran lain otomatis dialihkan kembali ke portalnya sendiri.

## Catatan
- Password lama (teks biasa) tetap bisa dipakai; otomatis di-hash saat login pertama.
- Akun baru yang dibuat admin langsung tersimpan sebagai hash.
- Pengajuan pembelian pengepul memakai harga per kg dari tabel `sampah`.
