<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        $code = rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $code,
                'created_at' => Carbon::now()
            ]
        );

        Mail::send([], [], function ($message) use ($request, $code) {
            $message->to($request->email)
                ->subject('Kode Reset Password YoKaji')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto;'>
                        <h2 style='color: #16a34a;'>YoKaji</h2>
                        <p>Kode reset password kamu:</p>
                        <h1 style='letter-spacing: 8px; color: #16a34a;'>{$code}</h1>
                        <p style='color: #888;'>Kode berlaku selama 10 menit. Abaikan email ini jika kamu tidak meminta reset password.</p>
                    </div>
                ");
        });

        return response()->json(['message' => 'Kode reset password telah dikirim ke email kamu.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'code'     => 'required',
            'password' => 'required|min:6',
        ]);

        $user = DB::table('users')->where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->code)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Kode salah atau tidak valid.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['message' => 'Kode sudah kadaluarsa.'], 422);
        }

        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password berhasil direset.']);
    }
}