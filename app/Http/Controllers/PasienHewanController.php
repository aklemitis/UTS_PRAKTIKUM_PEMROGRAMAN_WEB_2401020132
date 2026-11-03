<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PDO;
use Throwable;

class PasienHewanController extends Controller
{
    /** Pesan aman untuk pengguna; detail teknis hanya masuk log lewat report(). */
    private const PESAN_AMAN = 'Terjadi kesalahan pada sistem. Silakan coba lagi beberapa saat lagi.';

    /** Mengambil objek PDO dari koneksi Laravel. */
    private function pdo(): PDO
    {
        return DB::connection()->getPdo();
    }

    /** Daftar jenis hewan (tabel master) untuk dropdown form dan filter. */
    private function daftarJenis(PDO $pdo): array
    {
        $stmt = $pdo->prepare('SELECT id, nama_jenis FROM jenis_hewan ORDER BY nama_jenis ASC');
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Sanitasi: trim + strip_tags untuk teks, trim untuk angka. */
    private function bersihkan(Request $request): array
    {
        return [
            'kode_pasien'    => trim(strip_tags((string) $request->input('kode_pasien', ''))),
            'nama_hewan'     => trim(strip_tags((string) $request->input('nama_hewan', ''))),
            'nomor_rekam'    => trim(strip_tags((string) $request->input('nomor_rekam', ''))),
            'usia_tahun'     => trim((string) $request->input('usia_tahun', '')),
            'jenis_hewan_id' => trim((string) $request->input('jenis_hewan_id', '')),
        ];
    }

    /**
     * Validasi server-side (format) + pengecekan ke basis data lewat PDO
     * (duplikat dan keberadaan jenis hewan). $abaikanId dipakai saat Update
     * agar data itu sendiri tidak dianggap duplikat.
     */
    private function validasi(array $data, ?int $abaikanId = null)
    {
        $validator = Validator::make($data, [
            'kode_pasien'    => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
            'nama_hewan'     => ['required', 'string', 'min:2', 'max:100'],
            'nomor_rekam'    => ['required', 'string', 'min:5', 'max:30'],
            'usia_tahun'     => ['required', 'integer', 'between:0,30'],
            'jenis_hewan_id' => ['required', 'integer'],
        ], [
            'kode_pasien.required'    => 'Kode pasien wajib diisi.',
            'kode_pasien.regex'       => 'Kode pasien harus tepat 10 karakter alfanumerik (huruf dan angka).',
            'nama_hewan.required'     => 'Nama hewan wajib diisi.',
            'nama_hewan.min'          => 'Nama hewan minimal 2 karakter.',
            'nama_hewan.max'          => 'Nama hewan maksimal 100 karakter.',
            'nomor_rekam.required'    => 'Nomor rekam medis wajib diisi.',
            'nomor_rekam.min'         => 'Nomor rekam medis minimal 5 karakter.',
            'nomor_rekam.max'         => 'Nomor rekam medis maksimal 30 karakter.',
            'usia_tahun.required'     => 'Usia wajib diisi.',
            'usia_tahun.integer'      => 'Usia harus berupa bilangan bulat.',
            'usia_tahun.between'      => 'Usia harus antara 0 sampai 30 tahun.',
            'jenis_hewan_id.required' => 'Jenis hewan wajib dipilih.',
            'jenis_hewan_id.integer'  => 'Jenis hewan tidak valid.',
        ]);

        $validator->after(function ($v) use ($data, $abaikanId) {
            $pdo = $this->pdo();
            $id  = $abaikanId ?? 0;

            if (! $v->errors()->has('kode_pasien')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM pasien_hewan WHERE kode_pasien = :kode AND id <> :id');
                $stmt->execute([':kode' => $data['kode_pasien'], ':id' => $id]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $v->errors()->add('kode_pasien', 'Kode pasien sudah digunakan pasien lain.');
                }
            }

            if (! $v->errors()->has('nomor_rekam')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM pasien_hewan WHERE nomor_rekam = :rekam AND id <> :id');
                $stmt->execute([':rekam' => $data['nomor_rekam'], ':id' => $id]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $v->errors()->add('nomor_rekam', 'Nomor rekam medis sudah digunakan pasien lain.');
                }
            }

            if (! $v->errors()->has('jenis_hewan_id')) {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM jenis_hewan WHERE id = :jenis');
                $stmt->execute([':jenis' => (int) $data['jenis_hewan_id']]);
                if ((int) $stmt->fetchColumn() === 0) {
                    $v->errors()->add('jenis_hewan_id', 'Jenis hewan yang dipilih tidak tersedia.');
                }
            }
        });

        return $validator;
    }

    /** Memeriksa apakah error berasal dari pelanggaran UNIQUE (kode MySQL 1062). */
    private function adalahDuplikat(Throwable $e): bool
    {
        return $e instanceof \PDOException
            && isset($e->errorInfo[1])
            && (int) $e->errorInfo[1] === 1062;
    }

    // ------------------------------------------------------------------
    // READ  (GET /pasien-hewan)  + pencarian + filter
    // ------------------------------------------------------------------
    public function index(Request $request)
    {
        $q      = trim(strip_tags((string) $request->query('q', '')));
        $filter = trim((string) $request->query('jenis_hewan_id', ''));

        $pasienList = [];
        $jenisList  = [];
        $dbError    = null;

        try {
            $pdo       = $this->pdo();
            $jenisList = $this->daftarJenis($pdo);

            $sql = 'SELECT p.id, p.kode_pasien, p.nama_hewan, p.nomor_rekam, p.usia_tahun,
                           p.jenis_hewan_id, j.nama_jenis
                    FROM pasien_hewan p
                    INNER JOIN jenis_hewan j ON j.id = p.jenis_hewan_id
                    WHERE 1 = 1';
            $params = [];

            if ($q !== '') {
                // Pencarian pada tiga kolom: kode_pasien, nama_hewan, nomor_rekam
                $sql .= ' AND (p.kode_pasien LIKE :q1 OR p.nama_hewan LIKE :q2 OR p.nomor_rekam LIKE :q3)';
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $params[':q1'] = $like;
                $params[':q2'] = $like;
                $params[':q3'] = $like;
            }

            if ($filter !== '' && ctype_digit($filter)) {
                // Filter berdasarkan tabel master (jenis_hewan_id)
                $sql .= ' AND p.jenis_hewan_id = :jenis';
                $params[':jenis'] = (int) $filter;
            }

            $sql .= ' ORDER BY p.id ASC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pasienList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            report($e);
            $dbError = self::PESAN_AMAN;
        }

        return view('pasien-hewan.index', [
            'pasienList' => $pasienList,
            'jenisList'  => $jenisList,
            'q'          => $q,
            'filter'     => $filter,
            'dbError'    => $dbError,
        ]);
    }

    // ------------------------------------------------------------------
    // CREATE  (POST /pasien-hewan)
    // ------------------------------------------------------------------
    public function store(Request $request)
    {
        $data = $this->bersihkan($request);

        try {
            $validator = $this->validasi($data);

            if ($validator->fails()) {
                return redirect()->route('pasien-hewan.index')
                    ->withErrors($validator)
                    ->withInput($data);
            }

            $stmt = $this->pdo()->prepare(
                'INSERT INTO pasien_hewan
                    (kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id, created_at, updated_at)
                 VALUES (:kode, :nama, :rekam, :usia, :jenis, NOW(), NOW())'
            );
            $stmt->execute([
                ':kode'  => $data['kode_pasien'],
                ':nama'  => $data['nama_hewan'],
                ':rekam' => $data['nomor_rekam'],
                ':usia'  => (int) $data['usia_tahun'],
                ':jenis' => (int) $data['jenis_hewan_id'],
            ]);

            return redirect()->route('pasien-hewan.index')
                ->with('sukses', 'Data pasien berhasil ditambahkan.');
        } catch (Throwable $e) {
            report($e);

            $pesan = $this->adalahDuplikat($e)
                ? 'Kode pasien atau nomor rekam medis sudah terdaftar.'
                : self::PESAN_AMAN;

            return redirect()->route('pasien-hewan.index')
                ->withInput($data)
                ->with('error', $pesan);
        }
    }

    // ------------------------------------------------------------------
    // Form edit  (GET /pasien-hewan/{id}/edit)
    // ------------------------------------------------------------------
    public function edit(int $id)
    {
        try {
            $pdo  = $this->pdo();
            $stmt = $pdo->prepare(
                'SELECT id, kode_pasien, nama_hewan, nomor_rekam, usia_tahun, jenis_hewan_id
                 FROM pasien_hewan WHERE id = :id'
            );
            $stmt->execute([':id' => $id]);
            $pasien = $stmt->fetch(PDO::FETCH_ASSOC);

            if (! $pasien) {
                return redirect()->route('pasien-hewan.index')
                    ->with('error', 'Data pasien tidak ditemukan.');
            }

            return view('pasien-hewan.edit', [
                'pasien'    => $pasien,
                'jenisList' => $this->daftarJenis($pdo),
            ]);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('pasien-hewan.index')
                ->with('error', self::PESAN_AMAN);
        }
    }

    // ------------------------------------------------------------------
    // UPDATE  (PUT /pasien-hewan/{id})
    // ------------------------------------------------------------------
    public function update(Request $request, int $id)
    {
        $data = $this->bersihkan($request);

        try {
            $pdo = $this->pdo();

            $cek = $pdo->prepare('SELECT COUNT(*) FROM pasien_hewan WHERE id = :id');
            $cek->execute([':id' => $id]);
            if ((int) $cek->fetchColumn() === 0) {
                return redirect()->route('pasien-hewan.index')
                    ->with('error', 'Data pasien tidak ditemukan.');
            }

            $validator = $this->validasi($data, $id);

            if ($validator->fails()) {
                return redirect()->route('pasien-hewan.edit', $id)
                    ->withErrors($validator)
                    ->withInput($data);
            }

            $stmt = $pdo->prepare(
                'UPDATE pasien_hewan
                 SET kode_pasien = :kode, nama_hewan = :nama, nomor_rekam = :rekam,
                     usia_tahun = :usia, jenis_hewan_id = :jenis, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':kode'  => $data['kode_pasien'],
                ':nama'  => $data['nama_hewan'],
                ':rekam' => $data['nomor_rekam'],
                ':usia'  => (int) $data['usia_tahun'],
                ':jenis' => (int) $data['jenis_hewan_id'],
                ':id'    => $id,
            ]);

            return redirect()->route('pasien-hewan.index')
                ->with('sukses', 'Data pasien berhasil diperbarui.');
        } catch (Throwable $e) {
            report($e);

            $pesan = $this->adalahDuplikat($e)
                ? 'Kode pasien atau nomor rekam medis sudah dipakai pasien lain.'
                : self::PESAN_AMAN;

            return redirect()->route('pasien-hewan.edit', $id)
                ->withInput($data)
                ->with('error', $pesan);
        }
    }

    // ------------------------------------------------------------------
    // DELETE  (DELETE /pasien-hewan/{id})
    // ------------------------------------------------------------------
    public function destroy(int $id)
    {
        try {
            $stmt = $this->pdo()->prepare('DELETE FROM pasien_hewan WHERE id = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() === 0) {
                return redirect()->route('pasien-hewan.index')
                    ->with('error', 'Data pasien tidak ditemukan.');
            }

            return redirect()->route('pasien-hewan.index')
                ->with('sukses', 'Data pasien berhasil dihapus.');
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('pasien-hewan.index')
                ->with('error', self::PESAN_AMAN);
        }
    }
}
