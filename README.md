# 🎫 Helpdesk & Inventory Management System

Sistem berbasis web yang dibangun menggunakan **Laravel 12** dan **Filament v3** untuk membantu organisasi dalam mengelola:

- 🎟️ Tiket layanan pelanggan (Helpdesk Ticketing)
- 👥 Data dan histori pelanggan (Customer Tracking)
- 🧰 Inventarisasi perangkat (Inventory Management)

Dirancang untuk meningkatkan efisiensi layanan, respons terhadap permintaan pengguna, dan pengelolaan aset TI organisasi.

---

## 🚀 Fitur Unggulan

- 🔐 **Manajemen Role & Akses Pengguna**  
  Kelola hak akses berbasis role menggunakan **Spatie Permission** atau **Filament Shield**.

- 💻 **Manajemen Perangkat (Inventory CRUD)**  
  Tambah, ubah, hapus, dan pantau perangkat/alat secara menyeluruh.

- 🆘 **Sistem Helpdesk Ticketing + SLA Timer**  
  Kelola tiket dan pantau waktu tanggap sesuai SLA (Service Level Agreement).

- 📥 **Import / Export Excel**  
  Kelola data masal dengan fitur import dan export melalui background queue.

- 🧑‍🤝‍🧑 **Manajemen Pengguna Lengkap**  
  Kontrol penuh atas pengguna dan hak aksesnya.

- 🧾 **Ekspor PDF Tiket**  
  Cetak detail tiket dalam bentuk PDF untuk dokumentasi atau laporan.

- 🕓 **Riwayat Pembaruan (Audit Trail)**  
  Pantau histori aktivitas atau perubahan dari setiap data.

---

## ⚙️ Teknologi yang Digunakan

| Komponen     | Teknologi                |
|--------------|---------------------------|
| Framework    | Laravel 12                |
| UI Admin     | Filament v3               |
| Basis Data   | MySQL                     |
| Web Server   | Apache                    |
| Bahasa       | PHP >= 8.3                |
| Frontend     | Vite (dengan Laravel Mix) |

---

## 🧑‍💻 Instalasi Lokal

Ikuti langkah-langkah berikut untuk menjalankan proyek secara lokal:

```bash
# Clone repo
git Clone https://github.com/Ahmad-Rizki21/Artacom-Customer.git
cd nama-project

# Instal dependency backend & frontend
composer install
npm install && npm run build

# Konfigurasi environment
cp .env.example .env
php artisan key:generate

# Migrasi dan seeder
php artisan migrate --seed

# Jalankan queue listener (untuk import/export)
php artisan queue:listen

# Jalankan server lokal
php artisan serve
```

> 💡 Pastikan database MySQL telah dibuat dan dikonfigurasi di file `.env`.

---

## 🌐 Panduan Deploy ke Server Ubuntu

### 1. Instalasi Paket Server

```bash
sudo apt update
sudo apt install apache2 mysql-server php php-cli php-mysql \
php-curl php-mbstring php-xml php-bcmath unzip curl git composer \
nodejs npm
```

### 2. Clone & Setup Project

```bash
cd /var/www/
sudo git clone https://github.com/Ahmad-Rizki21/Artacom-Customer.git
cd nama-project
sudo chown -R www-data:www-data .
```

### 3. Setup Laravel

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

### 4. Konfigurasi Virtual Host Apache

```bash
sudo nano /etc/apache2/sites-available/helpdesk.conf
```

Isi file:

```apacheconf
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/nama-project/public

    <Directory /var/www/nama-project/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/helpdesk_error.log
    CustomLog ${APACHE_LOG_DIR}/helpdesk_access.log combined
</VirtualHost>
```

Aktifkan dan restart Apache:

```bash
sudo a2ensite helpdesk.conf
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## 🔁 Menjalankan Queue Worker di Server

Gunakan supervisor untuk menjalankan queue secara otomatis:

### File konfigurasi supervisor `/etc/supervisor/conf.d/queue-worker.conf`

```ini
[program:laravel-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/nama-project/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/nama-project/storage/logs/laravel-queue.log
stopwaitsecs=3600
```

### Jalankan Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-worker:*
```

---

## 📄 Contoh `.env.example`

```dotenv
APP_NAME="Helpdesk App"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=helpdesk_db
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_FROM_ADDRESS=admin@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🤝 Kontribusi

- Laporkan bug atau request fitur di [Issues](https://github.com/Ahmad-Rizki21/Artacom-Customer/issues)
- Kirim Pull Request jika ingin menambahkan fitur atau perbaikan

---

## 📜 Lisensi

Proyek ini berada di bawah lisensi [MIT License](LICENSE).

---

> Dibuat dengan ❤️ oleh tim developer by Ahmad Rizki, menggunakan Laravel dan Filament.
