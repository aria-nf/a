<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

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

    public function eksporDenda(Request $request)
    {
        $validated = $request->validate([
            'bulan'  => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'format' => ['nullable', 'string', 'in:pdf,csv,xlsx'],
        ]);

        $bulan  = $validated['bulan'];
        $format = $validated['format'] ?? 'pdf';

        $process = new Process([
            'php',
            'artisan',
            'laporan:denda',
            "--bulan={$bulan}",
            "--format={$format}",
        ]);
        $process->run();

        return response()->json(['hasil' => trim($process->getOutput())]);
    }

    public function konversiFormat(Request $request)
    {
        $validated = $request->validate([
            'input'         => ['required', 'string', 'max:255'],
            'output_format' => ['required', 'string', 'in:pdf,docx,xlsx,pptx,odt'],
        ]);

        $inputFile = basename($validated['input']);
        $outputFmt = $validated['output_format'];

        $process = new Process([
            'libreoffice',
            '--headless',
            '--convert-to',
            $outputFmt,
            $inputFile,
        ]);
        $process->run();

        return response()->json(['status' => 'selesai']);
    }
}
