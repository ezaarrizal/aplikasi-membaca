<?php

// routes/api.php - MINIMAL VERSION untuk fix masalah options saja

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\GameController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        try {
            $userCount = \App\Models\User::count();
            $gameCount = \App\Models\Game::count();

            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'database' => [
                    'users' => $userCount,
                    'games' => $gameCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    });

    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/auth/user', [AuthController::class, 'user']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        // GURU ROUTES - Keep existing
        Route::middleware(['role:guru'])->prefix('guru')->group(function () {
            Route::prefix('users')->group(function () {
                Route::get('/', [UserManagementController::class, 'index']);
                Route::post('/', [UserManagementController::class, 'store']);
                Route::get('/statistics', [UserManagementController::class, 'statistics']);
                Route::get('/{user}', [UserManagementController::class, 'show']);
                Route::put('/{user}', [UserManagementController::class, 'update']);
                Route::delete('/{user}', [UserManagementController::class, 'destroy']);
                Route::post('/{user}/toggle-status', [UserManagementController::class, 'toggleUserStatus']);
            });

            Route::prefix('feedback')->group(function () {
                Route::get('/', [FeedbackController::class, 'index']);
                Route::post('/', [FeedbackController::class, 'store']);
                Route::get('/{feedback}', [FeedbackController::class, 'show']);
                Route::put('/{feedback}', [FeedbackController::class, 'update']);
                Route::delete('/{feedback}', [FeedbackController::class, 'destroy']);
            });

            Route::get('/siswa-list', [FeedbackController::class, 'getSiswaList']);
        });

        // ⚡ SISWA ROUTES - FIXED untuk masalah options
        Route::middleware(['role:siswa'])->prefix('siswa')->group(function () {
            Route::prefix('games')->group(function () {
                Route::get('/', [GameController::class, 'index']);
                Route::post('/{game}/start', [GameController::class, 'startGame']);
                Route::post('/sessions/{session}/mark-video-watched', [GameController::class, 'markVideoWatched']);
                Route::get('/sessions/{session}/current-question', [GameController::class, 'getCurrentQuestion']); // ⚡ FIX
                Route::post('/sessions/{session}/questions/{question}/submit', [GameController::class, 'submitAnswer']); // ⚡ FIX
                Route::get('/sessions/{session}/results', [GameController::class, 'getSessionResults']);
            });

            Route::get('/badges', [GameController::class, 'getStudentBadges']);
        });

        // ORANGTUA ROUTES - Keep existing
        Route::middleware(['role:orangtua'])->prefix('orangtua')->group(function () {
            Route::prefix('feedback')->group(function () {
                Route::get('/', [FeedbackController::class, 'getForParent']);
                Route::post('/{feedback}/mark-read', [FeedbackController::class, 'markAsRead']);
                Route::get('/{feedback}', [FeedbackController::class, 'show']);
            });
        });
    });
});

// ⚡ DEBUG ROUTES - HANYA untuk troubleshoot masalah options
Route::prefix('debug')->group(function () {

    // Cek data options di database
    Route::get('/game-questions', function () {
        $questions = \App\Models\GameQuestion::all();

        $debug = [];
        foreach ($questions as $question) {
            $debug[] = [
                'id' => $question->id,
                'question_number' => $question->question_number,
                'letter' => $question->letter,
                'word' => $question->word,
                'raw_options_from_db' => $question->getAttributes()['options'] ?? 'NULL',
                'casted_options' => $question->options,
                'options_type' => gettype($question->options),
                'options_is_array' => is_array($question->options),
                'options_count' => is_array($question->options) ? count($question->options) : 0,
            ];
        }

        return response()->json([
            'total_questions' => count($questions),
            'debug_data' => $debug
        ], 200, [], JSON_PRETTY_PRINT);
    });

    // Test API simulation untuk game tertentu
    Route::get('/test-api/{gameId}', function ($gameId) {
        $user = \App\Models\User::where('role', 'siswa')->first();

        if (!$user) {
            return response()->json(['error' => 'No student found'], 404);
        }

        // Create test session
        $session = \App\Models\GameSession::create([
            'student_id' => $user->id,
            'game_id' => $gameId,
            'started_at' => now(),
            'status' => 'in_progress',
            'questions_completed' => []
        ]);

        $session->load(['game.questions' => function($query) {
            $query->orderBy('level')->orderBy('question_number');
        }]);

        $nextQuestion = $session->game->questions->first();

        if (!$nextQuestion) {
            $session->delete();
            return response()->json(['error' => 'No questions found'], 404);
        }

        // Test options processing
        $dbOptions = $nextQuestion->options;
        $options = collect($dbOptions)->map(function ($letter) {
            return [
                'letter' => strtoupper(trim($letter)),
                'audio_path' => 'assets/games/vowels/audio/letters/' . strtolower(trim($letter)) . '.mp3'
            ];
        })->toArray();

        $session->delete();

        return response()->json([
            'test_result' => 'SUCCESS',
            'question_info' => [
                'id' => $nextQuestion->id,
                'letter' => $nextQuestion->letter,
                'word' => $nextQuestion->word,
                'raw_options' => $nextQuestion->options,
                'raw_options_type' => gettype($nextQuestion->options),
            ],
            'processed_options' => $options,
            'options_count' => count($options),
            'message' => 'Options processing test - should show individual letters'
        ], 200, [], JSON_PRETTY_PRINT);
    });
});
