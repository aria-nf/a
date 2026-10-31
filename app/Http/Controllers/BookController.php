<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    // VULNERABILITY: SQL Injection — pencarian buku
    public function search(Request $request)
    {
        $judul   = $request->input('judul');
        $penulis = $request->input('penulis');
        $tahun   = $request->input('tahun');
        // Dangerous: semua parameter langsung masuk SQL
        $sql = "SELECT * FROM books
                WHERE judul LIKE '%$judul%'
                  AND penulis LIKE '%$penulis%'
                  AND tahun_terbit = '$tahun'";
        return response()->json(DB::select($sql));
    }

    // VULNERABILITY: SQL Injection — filter genre + sort
    public function byGenre(Request $request)
    {
        $genre = $request->input('genre');
        $sort  = $request->input('sort', 'judul'); // attacker controls ORDER BY
        $books = DB::select("SELECT * FROM books WHERE genre='$genre' ORDER BY $sort ASC");
        return response()->json($books);
    }

    // VULNERABILITY: IDOR — siapapun bisa hapus buku manapun
    public function hapusBuku($bookId)
    {
        // Tidak ada pengecekan: apakah user ini admin?
        DB::delete("DELETE FROM books WHERE id = $bookId");
        return response()->json(['pesan' => 'Buku berhasil dihapus']);
    }

    // VULNERABILITY: Mass Assignment
    public function tambahBuku(Request $request)
    {
        return response()->json(Book::create($request->all()));
    }
}
