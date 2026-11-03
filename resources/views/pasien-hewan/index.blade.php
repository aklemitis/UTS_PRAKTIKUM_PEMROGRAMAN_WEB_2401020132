<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem Pasien Klinik Hewan</title>
</head>
<body>
    <h1>Sistem Pasien Klinik Hewan</h1>

    {{-- Feedback operasi --}}
    @if (session('sukses'))
        <p><strong>[Berhasil]</strong> {{ session('sukses') }}</p>
    @endif

    @if (session('error'))
        <p><strong>[Gagal]</strong> {{ session('error') }}</p>
    @endif

    @if ($dbError)
        <p><strong>[Gagal]</strong> {{ $dbError }}</p>
    @endif

    @if ($errors->any())
        <p><strong>[Gagal]</strong> Data belum bisa disimpan. Periksa isian berikut:</p>
        <ul>
            @foreach ($errors->all() as $pesan)
                <li>{{ $pesan }}</li>
            @endforeach
        </ul>
    @endif

    {{-- Form tambah (Create) --}}
    <h2>Tambah Pasien</h2>
    <form method="POST" action="{{ route('pasien-hewan.store') }}" novalidate>
        @csrf
        <p>
            <label>Kode Pasien (10 karakter alfanumerik)<br>
                <input type="text" name="kode_pasien" value="{{ old('kode_pasien') }}">
            </label>
        </p>
        <p>
            <label>Nama Hewan<br>
                <input type="text" name="nama_hewan" value="{{ old('nama_hewan') }}">
            </label>
        </p>
        <p>
            <label>Nomor Rekam Medis<br>
                <input type="text" name="nomor_rekam" value="{{ old('nomor_rekam') }}">
            </label>
        </p>
        <p>
            <label>Usia (tahun, 0-30)<br>
                <input type="text" name="usia_tahun" value="{{ old('usia_tahun') }}">
            </label>
        </p>
        <p>
            <label>Jenis Hewan<br>
                <select name="jenis_hewan_id">
                    <option value="">-- Pilih jenis hewan --</option>
                    @foreach ($jenisList as $jenis)
                        <option value="{{ $jenis['id'] }}" @selected((string) old('jenis_hewan_id') === (string) $jenis['id'])>
                            {{ $jenis['nama_jenis'] }}
                        </option>
                    @endforeach
                </select>
            </label>
        </p>
        <p><button type="submit">Simpan</button></p>
    </form>

    <hr>

    {{-- Form pencarian + filter --}}
    <h2>Daftar Pasien</h2>
    <form method="GET" action="{{ route('pasien-hewan.index') }}">
        <label>Cari (kode / nama hewan / no. rekam):
            <input type="text" name="q" value="{{ $q }}">
        </label>
        <label>Jenis hewan:
            <select name="jenis_hewan_id">
                <option value="">Semua jenis</option>
                @foreach ($jenisList as $jenis)
                    <option value="{{ $jenis['id'] }}" @selected($filter === (string) $jenis['id'])>
                        {{ $jenis['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </label>
        <button type="submit">Cari / Filter</button>
        <a href="{{ route('pasien-hewan.index') }}">Reset</a>
    </form>

    <br>

    {{-- Tabel data --}}
    <table border="1" cellpadding="4" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Pasien</th>
                <th>Nama Hewan</th>
                <th>No. Rekam</th>
                <th>Usia (tahun)</th>
                <th>Status Usia</th>
                <th>Jenis Hewan</th>
                <th>Edit</th>
                <th>Hapus</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pasienList as $pasien)
                @php($status = statusUsiaHewan((int) $pasien['usia_tahun']))
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $pasien['kode_pasien'] }}</td>
                    <td>{{ $pasien['nama_hewan'] }}</td>
                    <td>{{ $pasien['nomor_rekam'] }}</td>
                    <td>{{ $pasien['usia_tahun'] }}</td>
                    <td>
                        @if ($status === 'Dewasa')
                            <strong>{{ $status }}</strong>
                        @else
                            {{ $status }}
                        @endif
                    </td>
                    <td>{{ $pasien['nama_jenis'] }}</td>
                    <td><a href="{{ route('pasien-hewan.edit', $pasien['id']) }}">Edit</a></td>
                    <td>
                        <form method="POST" action="{{ route('pasien-hewan.destroy', $pasien['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">Tidak ada data pasien.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
