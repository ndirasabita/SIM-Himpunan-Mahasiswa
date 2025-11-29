# **SIM Himpunan Mahasiswa – Sistem Manajemen Program Kerja**

Sistem ini dirancang untuk membantu pengurus Himpunan Mahasiswa dalam mengelola program kerja, dokumentasi kegiatan, proposal, laporan, dan pendataan lainnya secara terpusat melalui platform berbasis web. Aplikasi ini dibuat menggunakan **PHP Native**, **MySQL**, dan menerapkan alur kerja **Agile / Extreme Programming**.

---

## **✨ Fitur Utama**

### **1. Autentikasi Pengguna**

* Login berdasarkan tiga peran: **Sekretaris**, **Ketua Pelaksana**, dan **Ketua Umum**.
* Hak akses berbeda pada setiap level pengguna.

### **2. Kelola Program Kerja**

* Tambah, edit, hapus program kerja.
* Pencarian dan filter berdasarkan kategori.
* Tampilan tabel yang dinamis dan responsif.

### **3. Manajemen Dokumentasi**

* Unggah dokumen penting: proposal, surat, foto, video, serta laporan kegiatan.
* Preview file sebelum diunggah.
* Hapus file satu per satu.
* Penyimpanan berdasarkan struktur folder yang rapi.

### **4. Kelola User dan Struktur Organisasi**

* CRUD data pengguna sesuai jabatan.
* Penyesuaian peran berdasarkan kebutuhan organisasi.

### **5. Dashboard & Statistik**

* Total program kerja.
* Data dokumentasi.
* Riwayat pengelolaan.

### **6. Manajemen Buku / Referensi (Jika ada)**

* Tambah buku, kategori, dan view count.
* Menampilkan buku populer.

---

## **🛠️ Teknologi yang Digunakan**

| Komponen           | Teknologi                                 |
| ------------------ | ----------------------------------------- |
| Bahasa Pemrograman | PHP Native                                |
| Database           | MySQL / MariaDB                           |
| Frontend           | HTML, CSS, JavaScript                     |
| Library            | Font Awesome, SweetAlert, AJAX (opsional) |
| Server             | XAMPP / Laragon                           |

---

## **📦 Cara Instalasi & Menjalankan Sistem**

### **1. Clone Repository**

```bash
git clone https://github.com/ndirasabita/SIM-Himpunan-Mahasiswa.git
```

### **2. Pindahkan Folder ke Server**

Tempatkan folder tersebut di:

* **XAMPP** → `htdocs/`
* **Laragon** → `www/`

### **3. Import Database**

1. Buka `phpMyAdmin`
2. Buat database baru, misal:

   ```
   sim_himpunan
   ```
3. Import file `.sql` dari folder:

   ```
   /database/sim_himpunan.sql
   ```

### **4. Konfigurasi Koneksi Database**

Edit file:

```
config.php
```

dan sesuaikan dengan server Anda:

```php
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sim_himpunan";
$conn = mysqli_connect($host, $user, $pass, $db);
?>
```

### **5. Jalankan Sistem**

Akses melalui browser:

```
http://localhost/SIM-Himpunan-Mahasiswa/
```

---

## **🔑 Akun Pengguna (Demo)**

| Peran           | Username   | Password |
| --------------- | ---------- | -------- |
| Sekretaris      | sekretaris | 12345    |
| Ketua Pelaksana | ketuplak   | 12345    |
| Ketua Umum      | ketum      | 12345    |

*(Ubah sesuai data pada database Anda).*

---

## **📁 Struktur Folder**

```
SIM-Himpunan-Mahasiswa/
│── assets/
│    ├── css/
│    ├── js/
│    └── images/
│
│── uploads/
│    ├── proposal/
│    ├── surat/
│    ├── foto/
│    └── video/
│
│── pages/
│── controllers/
│── database/
│── config.php
│── index.php
│── README.md
```

---

## **📌 Alur Sistem Secara Ringkas**

1. **User login**
2. Sistem membaca peran → menyesuaikan hak akses
3. Pengguna dapat:

   * Menambah/mengedit program kerja
   * Mengunggah dokumentasi
   * Melihat riwayat & laporan
4. Semua data tersimpan pada database dan dapat diunduh kapan pun.

---

## **📄 Lisensi**

Proyek ini dibuat untuk keperluan akademik dan pengembangan organisasi mahasiswa. Silakan modifikasi sesuai kebutuhan.
