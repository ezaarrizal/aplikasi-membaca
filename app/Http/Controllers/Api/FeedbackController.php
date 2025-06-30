<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\User;
use App\Models\ParentChildRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    /**
     * Create feedback (Guru only)
     */

    public function update(Request $request, $id)
{
    $request->validate([
        'judul' => 'required|string|max:255',
        'isi_feedback' => 'required|string',
        'kategori' => 'required|in:akademik,perilaku,prestasi,kehadiran,lainnya',
        'tingkat' => 'required|in:positif,netral,perlu_perhatian',
    ], [
        'judul.required' => 'Judul feedback wajib diisi',
        'isi_feedback.required' => 'Isi feedback wajib diisi',
        'kategori.required' => 'Kategori wajib dipilih',
        'tingkat.required' => 'Tingkat feedback wajib dipilih',
    ]);

    $feedback = Feedback::find($id);

    if (!$feedback) {
        return response()->json([
            'success' => false,
            'message' => 'Feedback tidak ditemukan'
        ], 404);
    }

    // Check authorization - only creator can update
    if ($feedback->guru_id !== Auth::id()) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak memiliki akses untuk mengubah feedback ini'
        ], 403);
    }

    try {
        $feedback->update([
            'judul' => $request->judul,
            'isi_feedback' => $request->isi_feedback,
            'kategori' => $request->kategori,
            'tingkat' => $request->tingkat,
        ]);

        $feedback->load(['guru', 'siswa']);

        Log::info('Feedback updated', [
            'feedback_id' => $feedback->id,
            'guru_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil diperbarui',
            'data' => [
                'feedback' => [
                    'id' => $feedback->id,
                    'judul' => $feedback->judul,
                    'isi_feedback' => $feedback->isi_feedback,
                    'kategori' => $feedback->kategori,
                    'tingkat' => $feedback->tingkat,
                    'guru' => [
                        'id' => $feedback->guru->id,
                        'nama' => $feedback->guru->nama,
                    ],
                    'siswa' => [
                        'id' => $feedback->siswa->id,
                        'nama' => $feedback->siswa->nama,
                    ],
                    'created_at' => $feedback->created_at,
                    'updated_at' => $feedback->updated_at,
                    'formatted_date' => $feedback->formatted_created_at,
                ]
            ]
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to update feedback', [
            'error' => $e->getMessage(),
            'feedback_id' => $id,
            'guru_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal memperbarui feedback: ' . $e->getMessage(),
        ], 500);
    }
}

    public function destroy($id)
{
    $feedback = Feedback::find($id);

    if (!$feedback) {
        return response()->json([
            'success' => false,
            'message' => 'Feedback tidak ditemukan'
        ], 404);
    }

    // Check authorization - only creator can delete
    if ($feedback->guru_id !== Auth::id()) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak memiliki akses untuk menghapus feedback ini'
        ], 403);
    }

    try {
        $feedback->delete();

        Log::info('Feedback deleted', [
            'feedback_id' => $id,
            'guru_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback berhasil dihapus'
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to delete feedback', [
            'error' => $e->getMessage(),
            'feedback_id' => $id,
            'guru_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus feedback: ' . $e->getMessage(),
        ], 500);
    }
}
    public function getSiswaList()
{
    try {
        $siswaList = User::siswa()
                        ->active()
                        ->select('id', 'nama', 'username', 'is_active') // ← TAMBAHKAN is_active
                        ->orderBy('nama')
                        ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa berhasil diambil',
            'data' => [
                'siswa' => $siswaList
            ]
        ]);
    } catch (\Exception $e) {
        Log::error('Failed to get siswa list', [
            'error' => $e->getMessage(),
            'guru_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Gagal mengambil daftar siswa: ' . $e->getMessage(),
        ], 500);
    }
}
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:users,id',
            'judul' => 'required|string|max:255',
            'isi_feedback' => 'required|string',
            'kategori' => 'required|in:akademik,perilaku,prestasi,kehadiran,lainnya',
            'tingkat' => 'required|in:positif,netral,perlu_perhatian',
        ], [
            'siswa_id.required' => 'Siswa wajib dipilih',
            'siswa_id.exists' => 'Siswa tidak ditemukan',
            'judul.required' => 'Judul feedback wajib diisi',
            'isi_feedback.required' => 'Isi feedback wajib diisi',
            'kategori.required' => 'Kategori wajib dipilih',
            'tingkat.required' => 'Tingkat feedback wajib dipilih',
        ]);

        // Validasi siswa exists dan role = siswa
        $siswa = User::find($request->siswa_id);
        if (!$siswa || !$siswa->isSiswa()) {
            return response()->json([
                'success' => false,
                'message' => 'User yang dipilih bukan siswa'
            ], 400);
        }

        try {
            $feedback = Feedback::create([
                'guru_id' => Auth::id(),
                'siswa_id' => $request->siswa_id,
                'judul' => $request->judul,
                'isi_feedback' => $request->isi_feedback,
                'kategori' => $request->kategori,
                'tingkat' => $request->tingkat,
            ]);

            $feedback->load(['guru', 'siswa']);

            Log::info('Feedback created', [
                'feedback_id' => $feedback->id,
                'guru_id' => Auth::id(),
                'siswa_id' => $request->siswa_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feedback berhasil dikirim',
                'data' => [
                    'feedback' => [
                        'id' => $feedback->id,
                        'judul' => $feedback->judul,
                        'isi_feedback' => $feedback->isi_feedback,
                        'kategori' => $feedback->kategori,
                        'tingkat' => $feedback->tingkat,
                        'guru' => [
                            'id' => $feedback->guru->id,
                            'nama' => $feedback->guru->nama,
                        ],
                        'siswa' => [
                            'id' => $feedback->siswa->id,
                            'nama' => $feedback->siswa->nama,
                        ],
                        'created_at' => $feedback->created_at,
                        'formatted_date' => $feedback->formatted_created_at,
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create feedback', [
                'error' => $e->getMessage(),
                'guru_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim feedback: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get feedback list for Guru
     */
    public function index(Request $request)
    {
        $query = Feedback::with(['guru', 'siswa'])
                        ->where('guru_id', Auth::id());

        // Filter by siswa
        if ($request->has('siswa_id') && $request->siswa_id) {
            $query->where('siswa_id', $request->siswa_id);
        }

        // Filter by kategori
        if ($request->has('kategori') && $request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        // Filter by tingkat
        if ($request->has('tingkat') && $request->tingkat) {
            $query->where('tingkat', $request->tingkat);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('isi_feedback', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 10);
        $feedbacks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Feedback retrieved successfully',
            'data' => [
                'feedbacks' => $feedbacks->items(),
                'pagination' => [
                    'current_page' => $feedbacks->currentPage(),
                    'total_pages' => $feedbacks->lastPage(),
                    'per_page' => $feedbacks->perPage(),
                    'total' => $feedbacks->total(),
                    'has_next' => $feedbacks->hasMorePages(),
                    'has_prev' => $feedbacks->currentPage() > 1,
                ]
            ]
        ]);
    }

    /**
     * Get feedback for Orangtua (from their children)
     */
    public function getForParent(Request $request)
{
    $user = Auth::user();

    // COMMENT OUT parent-child restriction - orangtua bisa lihat semua feedback
    // $childrenIds = ParentChildRelationship::where('parent_id', $user->id)
    //                                     ->pluck('child_id')
    //                                     ->toArray();

    // if (empty($childrenIds)) {
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'No children found',
    //         'data' => [
    //             'feedbacks' => [],
    //             'unread_count' => 0,
    //             'children' => [],
    //         ]
    //     ]);
    // }

    // GET ALL FEEDBACKS instead of filtering by children
    $query = Feedback::with(['guru', 'siswa']); // Remove whereIn restriction

    // Filter by child (optional - if orangtua wants to filter by specific child)
    if ($request->has('child_id') && $request->child_id) {
        $query->where('siswa_id', $request->child_id);
    }

    // Filter by read status
    if ($request->has('unread_only') && $request->unread_only) {
        $query->where('is_read_by_parent', false);
    }

    $perPage = $request->get('per_page', 10);
    $feedbacks = $query->orderBy('created_at', 'desc')->paginate($perPage);

    // Get unread count (ALL feedbacks)
    $unreadCount = Feedback::where('is_read_by_parent', false)->count();

    // Get ALL children list (all siswa in system)
    $children = User::siswa()
                   ->active()
                   ->select('id', 'nama')
                   ->orderBy('nama')
                   ->get();

    return response()->json([
        'success' => true,
        'message' => 'Feedback retrieved successfully',
        'data' => [
            'feedbacks' => $feedbacks->items(),
            'unread_count' => $unreadCount,
            'children' => $children,
            'pagination' => [
                'current_page' => $feedbacks->currentPage(),
                'total_pages' => $feedbacks->lastPage(),
                'per_page' => $feedbacks->perPage(),
                'total' => $feedbacks->total(),
                'has_next' => $feedbacks->hasMorePages(),
                'has_prev' => $feedbacks->currentPage() > 1,
            ]
        ]
    ]);
}

    /**
     * Mark feedback as read by parent
     */
    public function markAsRead($id)
    {
        $user = Auth::user();

    // COMMENT OUT parent-child restriction
    // $childrenIds = ParentChildRelationship::where('parent_id', $user->id)
    //                                     ->pluck('child_id')
    //                                     ->toArray();

    $feedback = Feedback::find($id);

    if (!$feedback) {
        return response()->json([
            'success' => false,
            'message' => 'Feedback tidak ditemukan'
        ], 404);
    }

    // REMOVE parent-child access check - orangtua bisa mark as read semua feedback
    // if (!in_array($feedback->siswa_id, $childrenIds)) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Tidak memiliki akses ke feedback ini'
    //     ], 403);
    // }

    $feedback->markAsRead();

    return response()->json([
        'success' => true,
        'message' => 'Feedback berhasil ditandai sebagai dibaca'
    ]);
    }

    /**
     * Get feedback detail
     */
    public function show($id)
    {
        $feedback = Feedback::with(['guru', 'siswa'])->find($id);

    if (!$feedback) {
        return response()->json([
            'success' => false,
            'message' => 'Feedback tidak ditemukan'
        ], 404);
    }

    $user = Auth::user();

    

    return response()->json([
        'success' => true,
        'message' => 'Feedback retrieved successfully',
        'data' => [
            'feedback' => $feedback
        ]
    ]);

    }


}
