CREATE DATABASE IF NOT EXISTS sakuin CHARACTER SET utf8mb4;
USE sakuin;
CREATE TABLE users (
  id_user INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) NULL,
  nim VARCHAR(20) NULL,
  foto VARCHAR(255) NULL
);
CREATE TABLE kategori (
  id_kategori INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nama_kategori VARCHAR(50) NOT NULL,
  jenis ENUM('Pemasukan','Pengeluaran') NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);
CREATE TABLE transaksi (
  id_transaksi INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_kategori INT NOT NULL,
  tanggal DATE NOT NULL,
  nominal DECIMAL(12,2) NOT NULL,
  keterangan VARCHAR(200) NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE RESTRICT
);

-- Fitur Anggaran (batas pengeluaran) dan Tabungan
CREATE TABLE anggaran (
  id_anggaran INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  id_kategori INT NULL,
  batas DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
  FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE CASCADE
);
CREATE TABLE tabungan (
  id_tabungan INT AUTO_INCREMENT PRIMARY KEY,
  id_user INT NOT NULL,
  nama VARCHAR(80) NOT NULL,
  target DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);
CREATE TABLE setoran (
  id_setoran INT AUTO_INCREMENT PRIMARY KEY,
  id_tabungan INT NOT NULL,
  id_user INT NOT NULL,
  tanggal DATE NOT NULL,
  nominal DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (id_tabungan) REFERENCES tabungan(id_tabungan) ON DELETE CASCADE,
  FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);
