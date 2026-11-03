<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Pasien Hewan</title>
</head>
<body>
    <h1>Edit Pasien Hewan</h1>

    @if (session('error'))
        <p><strong>[Gagal]</strong> {{ session('error') }}</p>
    @endif

    @if ($errors->any())
        <p><strong>[Gagal]</strong> Perubahan belum bisa disimpan. Periksa isian berikut:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('pasien-hewan.update', $pasien['id']) }}" novalidate>
        @csrf
        @method('PUT')
        <p>
            <label>Kode Pasien (10 karakter alfanumerik)<br>
                <input type="text" name="kode_pasien" value="{{ old('kode_pasien', $pasien['kode_pasien']) }}">
            </label>
        </p>
        <p>
            <label>Nama Hewan<br>
                <input type="text" name="nama_hewan" value="{{ old('nama_hewan', $pasien['nama_hewan']) }}">
            </label>
        </p>
        <p>
            <label>Nomor Rekam Medis<br>
                <input type="text" name="nomor_rekam" value="{{ old('nomor_rekam', $pasien['nomor_rekam']) }}">
            </label>
        </p>
        <p>
            <label>Usia (tahun, 0-30)<br>
                <input type="text" name="usia_tahun" value="{{ old('usia_tahun', $pasien['usia_tahun']) }}">
            </label>
        </p>
        <p>
            <label>Jenis Hewan<br>
                <select name="jenis_hewan_id">
                    <option value="">-- Pilih jenis hewan --</option>
                    @foreach ($jenisList as $jenis)
                        <option value="{{ $jenis['id'] }}"
                            @selected((string) old('jenis_hewan_id', $pasien['jenis_hewan_id']) === (string) $jenis['id'])>
                            {{ $jenis['nama_jenis'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>
        <p>
            <button type="submit">Simpan Perubahan</button>
            <a href="{{ route('pasien-hewan.index') }}">Batal</a>
        </p>
    </form>
</body>
</html>
