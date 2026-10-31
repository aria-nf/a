<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function search(Request $request)
    {
        $judul   = $request->input('judul', '');
        $penulis = $request->input('penulis', '');
        $tahun   = $request->input('tahun');

        $sql = "SELECT * FROM books
                WHERE judul LIKE ?
                  AND penulis LIKE ?
                  AND tahun_terbit = ?";

        $books = DB::select($sql, [
            '%' . $judul . '%',
            '%' . $penulis . '%',
            $tahun,
        ]);

        return response()->json($books);
    }

    public function byGenre(Request $request)
    {
        $genre = $request->input('genre');

        $sort = match ($request->input('sort', 'judul')) {
            'penulis'      => 'penulis',
            'tahun_terbit' => 'tahun_terbit',
            'genre'        => 'genre',
            default        => 'judul',
        };

        $books = DB::select(
            "SELECT * FROM books WHERE genre = ? ORDER BY {$sort} ASC",
            [$genre]
        );

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
