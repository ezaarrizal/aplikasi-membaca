<?php
// app/Http/Controllers/Api/AuthController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user dan generate token
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
                   ->where('is_active', true)
                   ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah'
            ], 401);
        }

        // Generate token dengan abilities berdasarkan role
        $abilities = $this->getTokenAbilities($user->role);
        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        // Log login activity
        Log::info('User login', [
            'user_id' => $user->id,
            'role' => $user->role,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'nama' => $user->nama,
                    'role' => $user->role,
                ],
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    /**
     * Register user baru (hanya guru yang bisa register siswa/orangtua)
     */
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users',
            'email' => 'nullable|string|email|unique:users',
            'password' => 'required|string|min:6',
            'nama' => 'required|string',
            'role' => 'required|in:guru,siswa,orangtua',
        ]);

        // Cek authorization: hanya guru yang bisa register user lain
        if (auth()->check()) {
            $currentUser = auth()->user();
            if ($currentUser->role !== 'guru') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya guru yang dapat mendaftarkan user baru'
                ], 403);
            }
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'nama' => $request->nama,
            'role' => $request->role,
        ]);

        Log::info('User registered', [
            'new_user_id' => $user->id,
            'role' => $user->role,
            'registered_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil didaftarkan',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'nama' => $user->nama,
                    'role' => $user->role,
                ]
            ]
        ], 201);
    }

    /**
     * Get current authenticated user
     */
    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $request->user()->id,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'nama' => $request->user()->nama,
                    'role' => $request->user()->role,
                ]
            ]
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        Log::info('User logout', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Logout dari semua device (revoke all tokens)
     */
    public function logoutAll(Request $request)
    {
        $user = $request->user();

        // Revoke all tokens
        $request->user()->tokens()->delete();

        Log::info('User logout all devices', [
            'user_id' => $user->id,
            'role' => $user->role,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout dari semua device berhasil'
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Revoke all tokens untuk force re-login
        $user->tokens()->delete();

        Log::info('Password changed', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah. Silakan login kembali.'
        ]);
    }

    /**
     * Get token abilities berdasarkan role
     */
    private function getTokenAbilities($role)
    {
        switch ($role) {
            case 'guru':
                return [
                    'guru:read',
                    'guru:write',
                    'siswa:read',
                    'siswa:write',
                    'feedback:create',
                    'rekap:view'
                ];
            case 'siswa':
                return [
                    'siswa:read',
                    'siswa:write',
                    'permainan:play',
                    'profil:view'
                ];
            case 'orangtua':
                return [
                    'orangtua:read',
                    'anak:view',
                    'feedback:view'
                ];
            default:
                return [];
        }
    }
}
