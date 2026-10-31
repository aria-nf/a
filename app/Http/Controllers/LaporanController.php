<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaporanController extends Controller
{
    // VULNERABILITY: Path Traversal — unduh laporan sembarang file
    public function unduhLaporan(Request $request)
    {
        $namaFile = $request->input('file'); // mis: "laporan_jan.pdf"
        // Penyerang: file=../../.env atau ../../../../etc/passwd
        $path = storage_path('laporan/' . $namaFile);
        return response()->download($path);
    }

    // VULNERABILITY: Path Traversal — preview sampul buku
    public function previewSampul(Request $request)
    {
        $gambar = $request->input('gambar');
        // Tidak ada validasi path sama sekali
        return response(file_get_contents(public_path($gambar)))->header('Content-Type', 'image/jpeg');
    }

    // VULNERABILITY: Command Injection — ekspor laporan denda
    public function eksporDenda(Request $request)
    {
        $bulan  = $request->input('bulan');  // "2024-01; cat /etc/passwd"
        $format = $request->input('format', 'pdf');
        $result = exec("php artisan laporan:denda --bulan=$bulan --format=$format");
        return response()->json(['hasil' => $result]);
    }

    // VULNERABILITY: Command Injection — konversi file
    public function konversiFormat(Request $request)
    {
        $inputFile  = $request->input('input');
        $outputFmt  = $request->input('output_format'); // user-controlled
        $cmd = "libreoffice --headless --convert-to $outputFmt $inputFile";
        shell_exec($cmd);
        return response()->json(['status' => 'selesai']);
    }
}
