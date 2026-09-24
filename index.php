<?php
// ===== BAGIAN 1: TRAIT (Anggota 1) =====
trait LogTrait {
    public function catat(string $aksi): void {
        $waktu = date('Y-m-d H:i:s');
        file_put_contents('log.txt', "[$waktu] $aksi\n", FILE_APPEND);
    }
}

// ===== BAGIAN 2: CLASS INDUK - ENCAPSULATION (Anggota 2) =====
class Barang {
    private string $nama;
    private int $stok;
    private float $harga;

    public function __construct(string $nama, int $stok, float $harga) {
        $this->setNama($nama);
        $this->setStok($stok);
        $this->setHarga($harga);
    }

    public function getNama(): string { return $this->nama; }
    public function getStok(): int { return $this->stok; }
    public function getHarga(): float { return $this->harga; }

    public function setNama(string $nama): void {
        if (trim($nama) === '') throw new Exception("Nama tidak boleh kosong");
        $this->nama = $nama;
    }
    public function setStok(int $stok): void {
        if ($stok < 0) throw new Exception("Stok tidak boleh negatif");
        $this->stok = $stok;
    }
    public function setHarga(float $harga): void {
        if ($harga < 0) throw new Exception("Harga tidak boleh negatif");
        $this->harga = $harga;
    }

    // Method ini akan ditimpa (override) oleh class anak
    public function getJenis(): string { return 'Umum'; }
    public function getEkstra(): string { return ''; }
    public function getInfo(): string { return '-'; }

    public function toArray(): array {
        return [
            'jenis' => $this->getJenis(),
            'nama'  => $this->nama,
            'stok'  => $this->stok,
            'harga' => $this->harga,
            'ekstra' => $this->getEkstra(),
        ];
    }
}

// ===== BAGIAN 3: CLASS ANAK - INHERITANCE (Anggota 3) =====
class BarangMakanan extends Barang {
    private string $kedaluwarsa;

    public function __construct(string $nama, int $stok, float $harga, string $kedaluwarsa) {
        parent::__construct($nama, $stok, $harga);
        $this->kedaluwarsa = $kedaluwarsa;
    }

    public function getJenis(): string { return 'Makanan'; }
    public function getEkstra(): string { return $this->kedaluwarsa; }
    public function getInfo(): string { return "Exp: " . $this->kedaluwarsa; }
}

class BarangElektronik extends Barang {
    private string $garansi;

    public function __construct(string $nama, int $stok, float $harga, string $garansi) {
        parent::__construct($nama, $stok, $harga);
        $this->garansi = $garansi;
    }

    public function getJenis(): string { return 'Elektronik'; }
    public function getEkstra(): string { return $this->garansi; }
    public function getInfo(): string { return "Garansi: " . $this->garansi . " bulan"; }
}

// ===== BAGIAN 4: CLASS TOKO - BACA & SIMPAN JSON (Anggota 4) =====
class Toko {
    use LogTrait;

    private array $daftar = [];
    private string $file = 'data.json';

    public function __construct() {
        $this->muat();
    }

    private function buat(string $jenis, string $nama, int $stok, float $harga, string $ekstra): Barang {
        return match ($jenis) {
            'Makanan'    => new BarangMakanan($nama, $stok, $harga, $ekstra),
            'Elektronik' => new BarangElektronik($nama, $stok, $harga, $ekstra),
            default      => new Barang($nama, $stok, $harga),
        };
    }

    private function muat(): void {
        if (!file_exists($this->file)) return;
        $data = json_decode(file_get_contents($this->file), true) ?? [];
        foreach ($data as $d) {
            $this->daftar[] = $this->buat($d['jenis'], $d['nama'], $d['stok'], $d['harga'], $d['ekstra']);
        }
    }

    public function simpan(string $aksi): void {
        $data = array_map(fn($b) => $b->toArray(), $this->daftar);
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT));
        $this->catat($aksi);
    }

    // ===== BAGIAN 5: CRUD (Anggota 5) =====
    public function tambah(string $jenis, string $nama, int $stok, float $harga, string $ekstra): void {
        $barang = $this->buat($jenis, $nama, $stok, $harga, $ekstra);   // Create
        $this->daftar[] = $barang;
        $this->simpan("Tambah barang: $nama");
    }

    public function semua(): array {                                     // Read
        return $this->daftar;
    }

    public function cari(int $no): ?Barang {
        return $this->daftar[$no - 1] ?? null;
    }

    public function hapus(int $no): bool {                               // Delete
        $barang = $this->cari($no);
        if ($barang === null) return false;
        array_splice($this->daftar, $no - 1, 1);
        $this->simpan("Hapus barang: " . $barang->getNama());
        return true;
    }
    // Update: ubah lewat setter di Barang, lalu panggil $toko->simpan(...)
}

// ===== BAGIAN 6: MENU UTAMA (Anggota 6) =====
function tanya(string $teks): string {
    echo $teks;
    return trim(fgets(STDIN));
}

function tampil(Toko $toko): void {
    $semua = $toko->semua();
    if (!$semua) { echo "Belum ada barang.\n"; return; }
    foreach ($semua as $i => $b) {
        printf("%d. [%s] %s | stok: %d | Rp%s | %s\n",
            $i + 1, $b->getJenis(), $b->getNama(), $b->getStok(),
            number_format($b->getHarga(), 0, ',', '.'), $b->getInfo());
    }
}

$toko = new Toko();

while (true) {
    echo "\n=== SUPPLY TOKO ===\n1. Lihat barang\n2. Tambah barang\n3. Ubah barang\n4. Hapus barang\n0. Keluar\n";
    $pilih = tanya("Pilih: ");

    try {
        switch ($pilih) {
            case '1':
                tampil($toko);
                break;

            case '2':
                $pilihJenis = tanya("Jenis (1=Makanan, 2=Elektronik, 3=Umum): ");
                $jenis = ['1' => 'Makanan', '2' => 'Elektronik'][$pilihJenis] ?? 'Umum';
                $nama  = tanya("Nama: ");
                $stok  = (int) tanya("Stok: ");
                $harga = (float) tanya("Harga: ");
                $ekstra = '';
                if ($jenis === 'Makanan')    $ekstra = tanya("Tanggal kedaluwarsa (YYYY-MM-DD): ");
                if ($jenis === 'Elektronik') $ekstra = tanya("Garansi (bulan): ");
                $toko->tambah($jenis, $nama, $stok, $harga, $ekstra);
                echo "Barang ditambahkan.\n";
                break;

            case '3':
                tampil($toko);
                $barang = $toko->cari((int) tanya("Nomor barang: "));
                if (!$barang) { echo "Barang tidak ditemukan.\n"; break; }
                $nama  = tanya("Nama baru (kosongkan jika tidak diubah): ");
                $stok  = tanya("Stok baru (kosongkan jika tidak diubah): ");
                $harga = tanya("Harga baru (kosongkan jika tidak diubah): ");
                if ($nama !== '')  $barang->setNama($nama);
                if ($stok !== '')  $barang->setStok((int) $stok);
                if ($harga !== '') $barang->setHarga((float) $harga);
                $toko->simpan("Ubah barang: " . $barang->getNama());
                echo "Barang diubah.\n";
                break;

            case '4':
                tampil($toko);
                $ok = $toko->hapus((int) tanya("Nomor barang: "));
                echo $ok ? "Barang dihapus.\n" : "Barang tidak ditemukan.\n";
                break;

            case '0':
                echo "Sampai jumpa!\n";
                exit;

            default:
                echo "Pilihan tidak valid.\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}