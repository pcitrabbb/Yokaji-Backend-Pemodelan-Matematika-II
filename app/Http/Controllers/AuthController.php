<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Santri;
use App\Models\Penyimak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller {

    public function register(Request $request) {
        $request->validate([
            'nama'          => 'required|string',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'jenis_kelamin' => 'required',
            'nim'           => 'required|unique:santri',
            'fakultas'      => 'required',
            'prodi'         => 'required',
        ]);
          
        $user = User::create([
            'name'          => $request->nama,
            'nama'          => $request->nama,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'jenis_kelamin' => $request->jenis_kelamin,
            'role'          => 'santri',
        ]);
          
        Santri::create([
            'user_id'         => $user->id,
            'nim'             => $request->nim,
            'fakultas'        => $request->fakultas,
            'prodi'           => $request->prodi,
            'status_approval' => 'pending',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil, tunggu approval admin'
        ], 201);
    }

    public function registerPenyimak(Request $request) {
        $request->validate([
            'nama'          => 'required|string',
            'email'         => 'required|email|unique:users',
            'password'      => 'required|min:6',
            'jenis_kelamin' => 'required|string',
            'no_hp'         => 'required|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $user = User::create([
                    'name'          => $request->nama,
                    'nama'          => $request->nama,
                    'email'         => $request->email,
                    'password'      => Hash::make($request->password),
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'role'          => 'penyimak',
                ]);

                Penyimak::create([
                    'user_id'         => $user->id,
                    'no_hp'           => $request->no_hp,
                    'jenis_kelamin'   => $request->jenis_kelamin,
                    'status_approval' => 'pending',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registrasi gagal: ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Registrasi penyimak berhasil, tunggu approval admin'
        ], 201);
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        $user = auth()->user();

        if ($user->role === 'santri') {
            $santri = Santri::where('user_id', $user->id)->first();
            if ($santri && $santri->status_approval !== 'approved') {
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json([
                    'message' => 'Akun kamu belum diapprove oleh admin.'
                ], 403);
            }
        }

        if ($user->role === 'penyimak') {
            $penyimak = Penyimak::where('user_id', $user->id)->first();
            if ($penyimak && $penyimak->status_approval !== 'approved') {
                JWTAuth::invalidate(JWTAuth::getToken());
                return response()->json([
                    'message' => 'Akun kamu belum diapprove oleh admin.'
                ], 403);
            }
        }

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'            => $user->id,
                'nama'          => $user->nama ?? $user->name,
                'email'         => $user->email,
                'role'          => $user->role,
                'jenis_kelamin' => $user->jenis_kelamin,
            ]
        ]);
    }

    public function me() {
        $user = auth()->user();

        $extra = [];
        if ($user->role === 'santri') {
            $santri = Santri::where('user_id', $user->id)->first();
            $extra  = [
                'nim'      => $santri?->nim      ?? null,
                'fakultas' => $santri?->fakultas ?? null,
                'prodi'    => $santri?->prodi    ?? null,
            ];
        }

        return response()->json([
            'user' => array_merge([
                'id'            => $user->id,
                'nama'          => $user->nama ?? $user->name,
                'email'         => $user->email,
                'role'          => $user->role,
                'jenis_kelamin' => $user->jenis_kelamin,
            ], $extra)
        ]);
    }

    public function logout() {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logout berhasil']);
    }
    
    // ─── NEw: cek status approval tanpa auth ───────────────────────────────
    public function checkStatus(Request $request) {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($user->role === 'santri') {
            $santri = Santri::where('user_id', $user->id)->first();
            return response()->json(['status' => $santri?->status_approval ?? 'pending']);
        }

        if ($user->role === 'penyimak') {
            $penyimak = Penyimak::where('user_id', $user->id)->first();
            return response()->json(['status' => $penyimak?->status_approval ?? 'pending']);
        }

        return response()->json(['status' => 'pending']);
    }
}