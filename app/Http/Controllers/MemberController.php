<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    // VULNERABILITY: Weak hashing — MD5 untuk password anggota
    public function daftar(Request $request)
    {
        $password = md5($request->input('password')); // MD5 lemah
        DB::insert("INSERT INTO members (nama, nim, email, password, role)
                    VALUES (?, ?, ?, ?, 'anggota')", [
            $request->nama, $request->nim, $request->email, $password
        ]);
        return response()->json(['status' => 'Pendaftaran berhasil']);
    }

    // VULNERABILITY: SQL Injection + Weak hash di login
    public function masuk(Request $request)
    {
        $nim  = $request->input('nim');
        $pass = md5($request->input('password')); // MD5 lemah
        $member = DB::select("SELECT * FROM members WHERE nim='$nim' AND password='$pass'");
        if ($member) { session(['member_id' => $member[0]->id, 'role' => $member[0]->role]); }
        return response()->json($member ? ['status' => 'ok'] : ['error' => 'NIM atau password salah'], $member ? 200 : 401);
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
