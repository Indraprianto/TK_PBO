<?php
trait LogTrait {
    public function catat(string $aksi): void {
        $waktu = date('Y-m-d H:i:s');
        file_put_contents('log.txt', "[$waktu] $aksi\n", FILE_APPEND);
    }
}

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

    public function getJenis(): string { return 'Umum'; }
    public function getInfo(): string { return '-'; }
}

//INHERITANCE
class BarangMakanan extends Barang {
    private string $kedaluwarsa;

    public function __construct(string $nama, int $stok, float $harga, string $kedaluwarsa) {
        parent::__construct($nama, $stok, $harga);
        $this->kedaluwarsa = $kedaluwarsa;
    }

    public function getJenis(): string { return 'Makanan'; }
    public function getInfo(): string { return "Exp: " . $this->kedaluwarsa; }
}

class BarangElektronik extends Barang {
    private string $garansi;

    public function __construct(string $nama, int $stok, float $harga, string $garansi) {
        parent::__construct($nama, $stok, $harga);
        $this->garansi = $garansi;
    }

    public function getJenis(): string { return 'Elektronik'; }
    public function getInfo(): string { return "Garansi: " . $this->garansi . " bulan"; }
}

//BACA DARI TXT
class Toko {
    use LogTrait;

    private array $daftar = [];
    private string $file = 'data.txt';

    public function __construct() {
        $this->muat();
    }

    private function muat(): void {
        if (!file_exists($this->file)) return;

        $baris = file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($baris as $nomorBaris => $teks) {
            // format: jenis;nama;stok;harga;ekstra
            $kolom = explode(';', $teks);

            if (count($kolom) < 4) continue; // baris rusak/tidak lengkap, dilewati

            [$jenis, $nama, $stok, $harga] = $kolom;
            $ekstra = $kolom[4] ?? '';

            try {
                $barang = match (trim($jenis)) {
                    'Makanan'    => new BarangMakanan(trim($nama), (int) $stok, (float) $harga, trim($ekstra)),
                    'Elektronik' => new BarangElektronik(trim($nama), (int) $stok, (float) $harga, trim($ekstra)),
                    default      => new Barang(trim($nama), (int) $stok, (float) $harga),
                };
                $this->daftar[] = $barang;
            } catch (Exception $e) {
                // kalau ada baris dengan data tidak valid (misal stok negatif),
                // baris itu dilewati saja supaya halaman tetap tampil
                continue;
            }
        }

        $this->catat("Baca data (" . count($this->daftar) . " barang)");
    }

    public function semua(): array {
        return $this->daftar;
    }
}

//(READ SAJA)
$toko = new Toko();
$semua = $toko->semua();

if (!$semua) {
    echo "Belum ada barang.\n";
} else {
    foreach ($semua as $i => $b) {
        printf("%d. [%s] %s | stok: %d | Rp%s | %s . <br/>",
            $i + 1, $b->getJenis(), $b->getNama(), $b->getStok(),
            number_format($b->getHarga(), 0, ',', '.'), $b->getInfo());
    }
}