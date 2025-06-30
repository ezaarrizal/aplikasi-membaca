<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Get all users with pagination and filtering
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Search by name or username
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Order by created_at desc
        $query->orderBy('created_at', 'desc');

        // Paginate
        $perPage = $request->get('per_page', 10);
        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data' => [
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'has_next' => $users->hasMorePages(),
                    'has_prev' => $users->currentPage() > 1,
                ]
            ]
        ]);
    }

    /**
     * Store a new user
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users|min:3|max:50',
            'password' => 'required|string|min:6|max:50',
            'nama' => 'required|string|min:2|max:100',
            'role' => 'required|in:guru,siswa,orangtua',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah digunakan',
            'username.min' => 'Username minimal 3 karakter',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
            'nama.required' => 'Nama wajib diisi',
            'nama.min' => 'Nama minimal 2 karakter',
            'role.required' => 'Role wajib dipilih',
            'role.in' => 'Role harus guru, siswa, atau orangtua',
        ]);

        try {
            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'nama' => $request->nama,
                'role' => $request->role,
                'is_active' => true,
            ]);

            Log::info('User created', [
                'new_user_id' => $user->id,
                'role' => $user->role,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil ditambahkan',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'nama' => $user->nama,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show specific user
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama' => $user->nama,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Prevent updating own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengedit akun sendiri'
            ], 403);
        }

        $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:6|max:50',
            'nama' => 'required|string|min:2|max:100',
            'role' => 'required|in:guru,siswa,orangtua',
            'is_active' => 'boolean',
        ], [
            'username.required' => 'Username wajib diisi',
            'username.unique' => 'Username sudah digunakan',
            'username.min' => 'Username minimal 3 karakter',
            'password.min' => 'Password minimal 6 karakter',
            'nama.required' => 'Nama wajib diisi',
            'nama.min' => 'Nama minimal 2 karakter',
            'role.required' => 'Role wajib dipilih',
            'role.in' => 'Role harus guru, siswa, atau orangtua',
        ]);

        try {
            $updateData = [
                'username' => $request->username,
                'nama' => $request->nama,
                'role' => $request->role,
                'is_active' => $request->get('is_active', $user->is_active),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            Log::info('User updated', [
                'user_id' => $user->id,
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diupdate',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'nama' => $user->nama,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                        'updated_at' => $user->updated_at,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete user (soft delete by deactivating)
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Prevent deleting own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri'
            ], 403);
        }

        try {
            // Soft delete by deactivating
            $user->update(['is_active' => false]);

            // Revoke all tokens for this user
            $user->tokens()->delete();

            Log::info('User deactivated', [
                'user_id' => $user->id,
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil dinonaktifkan',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to deactivate user', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menonaktifkan user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Prevent toggling own account
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat mengubah status akun sendiri'
            ], 403);
        }

        try {
            $newStatus = !$user->is_active;
            $user->update(['is_active' => $newStatus]);

            // If deactivating, revoke all tokens
            if (!$newStatus) {
                $user->tokens()->delete();
            }

            $message = $newStatus ? 'User berhasil diaktifkan' : 'User berhasil dinonaktifkan';

            Log::info('User status toggled', [
                'user_id' => $user->id,
                'new_status' => $newStatus,
                'changed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'is_active' => $user->is_active,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle user status', [
                'user_id' => $id,
                'error' => $e->getMessage(),
                'changed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status user: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function statistics()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'by_role' => [
                'guru' => User::where('role', 'guru')->count(),
                'siswa' => User::where('role', 'siswa')->count(),
                'orangtua' => User::where('role', 'orangtua')->count(),
            ],
            'recent_registrations' => User::where('created_at', '>=', now()->subWeek())->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistics retrieved successfully',
            'data' => $stats
        ]);
    }
}
