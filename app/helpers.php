<?php

if (! function_exists('statusUsiaHewan')) {
    /**
     * Menentukan status usia pasien hewan.
     * 'Dewasa' jika usia >= 3 tahun, selain itu 'Muda'.
     */
    function statusUsiaHewan(int $usiaTahun): string
    {
        if ($usiaTahun >= 3) {
            return 'Dewasa';
        } else {
            return 'Muda';
        }
    }
}
