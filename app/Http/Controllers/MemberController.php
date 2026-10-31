<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function daftar(Request $request)
    {
        $validated = $request->validate([
            'nama'     => ['required', 'string', 'max:255'],
            'nim'      => ['required', 'string', 'max:50'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        DB::insert("INSERT INTO members (nama, nim, email, password, role)
                    VALUES (?, ?, ?, ?, 'anggota')", [
            $validated['nama'],
            $validated['nim'],
            $validated['email'],
            Hash::make($validated['password']),
        ]);

        return response()->json(['status' => 'Pendaftaran berhasil']);
    }

    public function masuk(Request $request)
    {
        $validated = $request->validate([
            'nim'      => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $member = DB::select('SELECT * FROM members WHERE nim = ? LIMIT 1', [$validated['nim']]);

        if ($member && Hash::check($validated['password'], $member[0]->password)) {
            session(['member_id' => $member[0]->id, 'role' => $member[0]->role]);
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['error' => 'NIM atau password salah'], 401);
    }

    // VULNERABILITY: IDOR — lihat riwayat peminjaman member lain
    public function riwayatPeminjaman($memberId)
    {
        // Harusnya: cek session member_id == $memberId
        $riwayat = DB::select("SELECT * FROM peminjaman WHERE member_id = $memberId ORDER BY tanggal_pinjam DESC");
        return response()->json($riwayat);
    }

    // VULNERABILITY: Mass Assignment
    public function updateProfil(Request $request, $id)
    {
        Member::find($id)->update($request->all()); // bisa ubah field 'role' jadi 'admin'
        return response()->json(['status' => 'Profil diperbarui']);
    }
}
