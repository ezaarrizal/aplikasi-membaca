<?php

// app/Http/Controllers/Api/GameController.php - UPDATED with Spelling Game Support
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameQuestion;
use App\Models\GameQuestionAttempt;
use App\Models\StudentBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    /**
     * Get current question for the session - UPDATED with Spelling Game support
     */
    public function getCurrentQuestion(Request $request, $sessionId)
    {
        try {
            $session = GameSession::where('id', $sessionId)
                ->where('student_id', Auth::id())
                ->with(['game.questions' => function($query) {
                    $query->orderBy('level')->orderBy('question_number');
                }])
                ->firstOrFail();

            $completedQuestions = $session->questions_completed ?? [];
            $allQuestions = $session->game->questions;

            // Find next question that is not completed
            $nextQuestion = $allQuestions->first(function ($question) use ($completedQuestions) {
                return !in_array($question->id, $completedQuestions);
            });

            if (!$nextQuestion) {
                return response()->json([
                    'success' => true,
                    'message' => 'All questions completed',
                    'data' => [
                        'all_completed' => true,
                        'session' => $session
                    ]
                ]);
            }

            $options = [];

            // ⚡ UPDATED: Handle different question types including Spelling Game
            if ($nextQuestion->question_type === 'vocal_fill' || $nextQuestion->question_type === 'fill_blank') {
                // Vocal game logic (existing)
                $dbOptions = $nextQuestion->options;
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['A', 'I', 'U', 'E', 'O'];
                }
                $options = collect($dbOptions)->map(function ($letter) {
                    return [
                        'letter' => strtoupper(trim($letter)),
                        'audio_path' => 'assets/games/vowels/audio/letters/' . strtolower(trim($letter)) . '.mp3'
                    ];
                })->toArray();
                shuffle($options);

            } elseif ($nextQuestion->question_type === 'find_difference') {
                // Detective Game Level 1 (existing)
                $dbOptions = $nextQuestion->options;
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['b', 'b', 'd'];
                }
                $options = collect($dbOptions)->map(function ($char) {
                    return [
                        'letter' => strtolower(trim($char)),
                        'audio_path' => 'assets/games/detective/audio/letters/' . strtolower(trim($char)) . '.mp3'
                    ];
                })->toArray();

            } elseif ($nextQuestion->question_type === 'drag_match') {
                // Detective Game Level 2 (existing)
                $dbOptions = $nextQuestion->options;
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['b', 'd', 'p'];
                }
                $options = collect($dbOptions)->map(function ($char) {
                    return [
                        'letter' => strtolower(trim($char)),
                        'audio_path' => 'assets/games/detective/audio/letters/' . strtolower(trim($char)) . '.mp3'
                    ];
                })->toArray();
                shuffle($options);

            } elseif ($nextQuestion->question_type === 'complete_word') {
                // ⚡ NEW: Spelling Game Level 1 - Complete Word
                $dbOptions = $nextQuestion->options;
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['sa', 'si', 'so']; // Default fallback
                }
                $options = collect($dbOptions)->map(function ($syllable) {
                    return [
                        'letter' => strtolower(trim($syllable)),
                        'audio_path' => 'assets/games/spelling/audio/' . strtolower(trim($syllable)) . '.mp3'
                    ];
                })->toArray();
                shuffle($options); // Shuffle options for variety

            } elseif ($nextQuestion->question_type === 'arrange_syllables') {
                // ⚡ NEW: Spelling Game Level 2 - Arrange Syllables
                $dbOptions = $nextQuestion->options;
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['sa', 'pu', 'bi', 'ru']; // Default fallback
                }
                $options = collect($dbOptions)->map(function ($syllable) {
                    return [
                        'letter' => strtolower(trim($syllable)),
                        'audio_path' => 'assets/games/spelling/audio/' . strtolower(trim($syllable)) . '.mp3'
                    ];
                })->toArray();
                // Don't shuffle for arrange_syllables - order is important for user to figure out

            } elseif ($nextQuestion->question_type === 'read_sentence') {
                // ⚡ NEW: Spelling Game Level 3 - Read Sentence
                // No options needed for reading sentences, just return empty array
                $options = [];

            } else {
                // Default/fallback handling (existing)
                $dbOptions = $nextQuestion->options ?? [];
                if (!is_array($dbOptions) || empty($dbOptions)) {
                    $dbOptions = ['a', 'i', 'u', 'e', 'o'];
                }
                $options = collect($dbOptions)->map(function ($char) {
                    return [
                        'letter' => strtolower(trim($char)),
                        'audio_path' => 'assets/games/detective/audio/letters/' . strtolower(trim($char)) . '.mp3'
                    ];
                })->toArray();
            }

            // Calculate current progress
            $currentProgressCount = count($completedQuestions) + 1;
            $totalQuestionsInGame = $session->game->total_questions;
            $progressPercentage = ($totalQuestionsInGame > 0) ? round(($currentProgressCount / $totalQuestionsInGame) * 100, 2) : 0;

            // ⚡ NEW: Add additional data for spelling game
            $additionalData = [];
            if ($session->game->title === 'Belajar Mengeja') {
                $additionalData = [
                    'word_pattern' => $nextQuestion->word_pattern ?? '', // For displaying pattern like "... + pu"
                    'full_word' => $nextQuestion->word, // Full word for reference
                    'correct_sequence' => $nextQuestion->question_type === 'arrange_syllables'
                        ? explode(',', $nextQuestion->letter) // Split correct sequence for level 2
                        : null,
                ];
            }

            Log::info('Game question loaded', [
                'question_id' => $nextQuestion->id,
                'question_type' => $nextQuestion->question_type,
                'level' => $nextQuestion->level,
                'game_title' => $session->game->title,
                'options_count' => count($options),
                'student_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Current question retrieved',
                'data' => [
                    'question' => $nextQuestion,
                    'options' => $options,
                    'progress' => [
                        'current' => $currentProgressCount,
                        'total' => $totalQuestionsInGame,
                        'percentage' => $progressPercentage
                    ],
                    'session' => $session,
                    'additional_data' => $additionalData, // ⚡ NEW: Additional spelling game data
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get current question', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'student_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get current question: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit question attempt - UPDATED with Spelling Game support
     */
    public function submitAnswer(Request $request, $sessionId, $questionId)
    {
        try {
            $request->validate([
                'selected_letter' => 'nullable|string', // For single answers
                'selected_sequence' => 'nullable|array', // For arrange syllables (Level 2)
                'teacher_observation' => 'nullable|string|max:1000',
                'is_correct' => 'nullable|boolean', // For teacher/parent evaluation
                'action_type' => 'nullable|string|in:answer,next', // For read_sentence (Level 3)
            ]);

            $session = GameSession::where('id', $sessionId)
                ->where('student_id', Auth::id())
                ->firstOrFail();

            $question = GameQuestion::findOrFail($questionId);

            $selectedAnswer = $request->selected_letter;
            $selectedSequence = $request->selected_sequence;
            $teacherObservation = $request->teacher_observation;
            $actionType = $request->action_type ?? 'answer';
            $isCorrect = false;
            $feedbackMessage = 'Selesai!';
            $feedbackAudio = 'assets/audio/completed.mp3';

            // ⚡ SPELLING GAME LOGIC
            if ($session->game->title === 'Belajar Mengeja') {

                if ($question->question_type === 'complete_word') {
                    // Level 1: Complete Word
                    $isCorrect = (strtolower($selectedAnswer) === strtolower($question->letter));
                    $feedbackMessage = $isCorrect ? 'Hebat! Jawabanmu benar!' : 'Coba lagi ya!';
                    $feedbackAudio = $isCorrect ? 'assets/audio/success.mp3' : 'assets/audio/try_again.mp3';

                } elseif ($question->question_type === 'arrange_syllables') {
                    // Level 2: Arrange Syllables
                    $correctSequence = explode(',', $question->letter);
                    $isCorrect = $selectedSequence && ($selectedSequence === $correctSequence);
                    $selectedAnswer = $selectedSequence ? implode(',', $selectedSequence) : '';
                    $feedbackMessage = $isCorrect ? 'Bagus! Urutan suku katanya benar!' : 'Urutan belum tepat, coba lagi!';
                    $feedbackAudio = $isCorrect ? 'assets/audio/success.mp3' : 'assets/audio/try_again.mp3';

                } elseif ($question->question_type === 'read_sentence') {
                    // Level 3: Read Sentence - always correct when user clicks "next"
                    $isCorrect = ($actionType === 'next');
                    $selectedAnswer = 'read_completed';
                    $feedbackMessage = 'Bagus! Lanjut ke kalimat berikutnya!';
                    $feedbackAudio = 'assets/audio/success.mp3';
                }

            } elseif ($session->game->title === 'Detektif Huruf') {
                // Detective Game logic (existing)
                if ($request->has('is_correct')) {
                    $isCorrect = $request->is_correct;
                    $feedbackMessage = $isCorrect ? 'Bagus sekali!' : 'Coba perhatikan lagi ya!';
                    $feedbackAudio = $isCorrect ? 'assets/audio/success.mp3' : 'assets/audio/try_again.mp3';
                } else {
                    $isCorrect = false;
                    $feedbackMessage = 'Jawaban sudah dicatat. Menunggu penilaian guru/orangtua.';
                    $feedbackAudio = 'assets/audio/noted.mp3';
                }

            } else {
                // Original logic for vocal games (existing)
                if ($question->question_type === 'vocal_fill' || $question->question_type === 'fill_blank') {
                    $isCorrect = (strtolower($selectedAnswer) === strtolower($question->letter));
                } elseif ($question->question_type === 'find_difference' || $question->question_type === 'drag_match') {
                    $isCorrect = (strtolower($selectedAnswer) === strtolower($question->letter));
                }

                if ($isCorrect) {
                    $feedbackMessage = 'Bagus!';
                    $feedbackAudio = 'assets/audio/success.mp3';
                } else {
                    $feedbackMessage = 'Coba lagi!';
                    $feedbackAudio = 'assets/audio/try_again.mp3';
                }
            }

            // Create or update attempt
            $existingAttempt = GameQuestionAttempt::where('game_session_id', $sessionId)
                ->where('game_question_id', $questionId)
                ->first();

            if ($existingAttempt) {
                $existingAttempt->update([
                    'attempted_at' => now(),
                    'completed' => $isCorrect,
                    'selected_answer' => $selectedAnswer,
                    'teacher_observation' => $teacherObservation,
                    'attempt_count' => $existingAttempt->attempt_count + 1
                ]);
                $attempt = $existingAttempt;
            } else {
                $attempt = GameQuestionAttempt::create([
                    'game_session_id' => $sessionId,
                    'game_question_id' => $questionId,
                    'attempted_at' => now(),
                    'completed' => $isCorrect,
                    'selected_answer' => $selectedAnswer,
                    'teacher_observation' => $teacherObservation,
                    'attempt_count' => 1
                ]);
            }

            // Mark question as completed based on game type
            if ($session->game->title === 'Detektif Huruf') {
                // Detective game: always mark as attempted (can be repeated)
                $session->markQuestionCompleted($questionId);
                $session->refresh();

                if ($session->isCompleted()) {
                    $this->awardBadge($session);
                }
            } elseif ($session->game->title === 'Belajar Mengeja') {
                // ⚡ NEW: Spelling game logic
                if ($question->question_type === 'read_sentence') {
                    // Level 3: Always mark as completed when "next" is clicked
                    $session->markQuestionCompleted($questionId);
                    $session->refresh();
                } else {
                    // Level 1 & 2: Only mark completed if correct
                    if ($isCorrect) {
                        $session->markQuestionCompleted($questionId);
                        $session->refresh();
                    }
                }

                if ($session->isCompleted()) {
                    $this->awardBadge($session);
                }
            } else {
                // Original logic: only mark completed if correct
                if ($isCorrect) {
                    $session->markQuestionCompleted($questionId);
                    $session->refresh();

                    if ($session->isCompleted()) {
                        $this->awardBadge($session);
                    }
                }
            }

            Log::info('Answer submitted', [
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'selected_answer' => $selectedAnswer,
                'is_correct' => $isCorrect,
                'game_title' => $session->game->title,
                'question_type' => $question->question_type,
                'student_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => $feedbackMessage,
                'data' => [
                    'is_correct' => $isCorrect,
                    'correct_answer' => $question->letter,
                    'attempt' => $attempt,
                    'session_completed' => $session->isCompleted(),
                    'feedback_audio' => $feedbackAudio,
                    'can_retry' => $session->game->title === 'Detektif Huruf' ||
                                  ($session->game->title === 'Belajar Mengeja' && !$isCorrect && $question->question_type !== 'read_sentence'),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to submit answer', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'question_id' => $questionId,
                'student_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to submit answer'
            ], 500);
        }
    }

    // ... (rest of methods remain the same - index, startGame, markVideoWatched)

    /**
     * Award badge to student - UPDATED with Spelling Game badge
     */
    private function awardBadge(GameSession $session)
    {
        try {
            $existingBadge = StudentBadge::where('student_id', $session->student_id)
                ->where('game_id', $session->game_id)
                ->first();

            if (!$existingBadge) {
                $badgeName = '';
                $badgeImagePath = '';
                $badgeDescription = '';

                if ($session->game->title === 'Permainan Huruf Vokal') {
                    $badgeName = 'Ahli Huruf Vokal';
                    $badgeImagePath = 'assets/badges/vowel_master.png';
                    $badgeDescription = 'Selamat! Kamu sudah mahir mengenali dan mengucapkan huruf vokal!';
                } elseif ($session->game->title === 'Detektif Huruf') {
                    $badgeName = 'Detektif Handal';
                    $badgeImagePath = 'assets/badges/detective_master.png';
                    $badgeDescription = 'Selamat! Kamu adalah detektif huruf yang sangat handal!';
                } elseif ($session->game->title === 'Belajar Mengeja') {
                    // ⚡ NEW: Spelling Game Badge
                    $badgeName = 'Ahli Mengeja';
                    $badgeImagePath = 'assets/badges/spelling_master.png';
                    $badgeDescription = 'Selamat! Kamu sudah mahir mengeja dan membaca kalimat sederhana!';
                } else {
                    $badgeName = 'Pencapai Hebat';
                    $badgeImagePath = 'assets/badges/default_badge.png';
                    $badgeDescription = 'Kamu telah menyelesaikan sebuah game!';
                }

                $badge = StudentBadge::create([
                    'student_id' => $session->student_id,
                    'game_id' => $session->game_id,
                    'badge_name' => $badgeName,
                    'badge_image_path' => $badgeImagePath,
                    'description' => $badgeDescription,
                    'earned_at' => now()
                ]);

                Log::info('Badge awarded', [
                    'badge_id' => $badge->id,
                    'student_id' => $session->student_id,
                    'game_id' => $session->game_id,
                    'badge_name' => $badgeName
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to award badge', [
                'error' => $e->getMessage(),
                'session_id' => $session->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get all available games for students
     */
    public function index(Request $request)
    {
        try {
            $games = Game::active()
                ->with(['questions'])
                ->get();

            $gamesArray = [];
            $studentId = Auth::id();

            foreach ($games as $game) {
                $gameArray = $game->toArray();

                $latestSession = GameSession::where('student_id', $studentId)
                    ->where('game_id', $game->id)
                    ->latest()
                    ->first();

                $gameArray['student_progress'] = [
                    'has_played' => $latestSession ? true : false,
                    'is_completed' => $latestSession && $latestSession->isCompleted(),
                    'progress_percentage' => $latestSession ? $latestSession->progress_percentage : 0,
                    'last_played' => $latestSession ? $latestSession->updated_at->toISOString() : null
                ];

                $badge = StudentBadge::where('student_id', $studentId)
                    ->where('game_id', $game->id)
                    ->first();

                $gameArray['student_progress']['has_badge'] = $badge ? true : false;
                $gameArray['student_progress']['badge'] = $badge ? $badge->toArray() : null;

                $gamesArray[] = $gameArray;
            }

            Log::info('Games list retrieved successfully', [
                'student_id' => $studentId,
                'games_count' => count($gamesArray)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Games retrieved successfully',
                'data' => $gamesArray
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve games', [
                'error' => $e->getMessage(),
                'student_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load games'
            ], 500);
        }
    }

    /**
     * Start a new game session
     */
    public function startGame(Request $request, $gameId)
    {
        try {
            $game = Game::active()->findOrFail($gameId);
            $studentId = Auth::id();

            // Check if there's an active session
            $activeSession = GameSession::where('student_id', $studentId)
                ->where('game_id', $gameId)
                ->where('status', 'in_progress')
                ->first();

            if ($activeSession) {
                $session = $activeSession;
                Log::info('Existing game session resumed', [
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                    'game_id' => $gameId
                ]);
            } else {
                // Create new session
                $session = GameSession::create([
                    'student_id' => $studentId,
                    'game_id' => $gameId,
                    'started_at' => now(),
                    'status' => 'in_progress',
                    'questions_completed' => []
                ]);

                Log::info('New game session started', [
                    'session_id' => $session->id,
                    'student_id' => $studentId,
                    'game_id' => $gameId
                ]);
            }

            // Load game with questions for the session context
            $session->load(['game.questions' => function($query) {
                $query->orderBy('level')->orderBy('question_number');
            }]);

            // Get the total questions for the game
            $totalQuestions = $game->questions()->count();
            if ($game->total_questions != $totalQuestions) {
                $game->update(['total_questions' => $totalQuestions]);
                $session->game->total_questions = $totalQuestions;
            }

            return response()->json([
                'success' => true,
                'message' => 'Game session started successfully',
                'data' => [
                    'session' => $session,
                    'has_video' => !empty($game->video_path),
                    'video_path' => $game->video_path
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start game', [
                'error' => $e->getMessage(),
                'game_id' => $gameId,
                'student_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start game'
            ], 500);
        }
    }

    /**
     * Mark video as watched (optional now)
     */
    public function markVideoWatched(Request $request, $sessionId)
    {
        try {
            $session = GameSession::where('id', $sessionId)
                ->where('student_id', Auth::id())
                ->firstOrFail();

            $session->update([
                'video_watched' => true,
                'video_completed_at' => now()
            ]);

            Log::info('Video marked as watched', [
                'session_id' => $sessionId,
                'student_id' => Auth::id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video marked as watched'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark video as watched', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'student_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark video as watched'
            ], 500);
        }
    }
    public function getStudentBadges(Request $request)
{
    try {
        $studentId = Auth::id();

        Log::info('Getting student badges', [
            'student_id' => $studentId
        ]);

        // Get all badges for current student with game information
        $badges = StudentBadge::where('student_id', $studentId)
            ->with(['game' => function($query) {
                $query->select('id', 'title', 'theme', 'skill_focus');
            }])
            ->orderBy('earned_at', 'desc') // Latest badges first
            ->get();

        // Format badges data for frontend
        $badgesData = $badges->map(function ($badge) {
            return [
                'id' => $badge->id,
                'student_id' => $badge->student_id,
                'game_id' => $badge->game_id,
                'badge_name' => $badge->badge_name,
                'description' => $badge->description,
                'badge_image_path' => $badge->badge_image_path,
                'earned_date' => $badge->earned_at->toISOString(), // Format untuk frontend
                'created_at' => $badge->created_at->toISOString(),
                'updated_at' => $badge->updated_at->toISOString(),
                'game' => $badge->game ? [
                    'id' => $badge->game->id,
                    'title' => $badge->game->title,
                    'theme' => $badge->game->theme,
                    'skill_focus' => $badge->game->skill_focus
                ] : null
            ];
        });

        Log::info('Student badges retrieved successfully', [
            'student_id' => $studentId,
            'badges_count' => $badges->count()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Badges retrieved successfully',
            'data' => $badgesData
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get student badges', [
            'error' => $e->getMessage(),
            'student_id' => Auth::id(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve badges'
        ], 500);
    }
}

/**
 * Get session results (bonus method untuk completing game flow)
 */
public function getSessionResults(Request $request, $sessionId)
{
    try {
        $session = GameSession::where('id', $sessionId)
            ->where('student_id', Auth::id())
            ->with(['game', 'questionAttempts'])
            ->firstOrFail();

        // Get badge earned for this game if any
        $badge = StudentBadge::where('student_id', Auth::id())
            ->where('game_id', $session->game_id)
            ->first();

        // Calculate session statistics
        $totalQuestions = $session->game->total_questions;
        $completedQuestions = count($session->questions_completed ?? []);
        $correctAnswers = $session->questionAttempts()
            ->where('completed', true)
            ->count();

        $sessionResults = [
            'session' => [
                'id' => $session->id,
                'student_id' => $session->student_id,
                'game_id' => $session->game_id,
                'started_at' => $session->started_at->toISOString(),
                'completed_at' => $session->completed_at?->toISOString(),
                'video_watched' => $session->video_watched,
                'video_completed_at' => $session->video_completed_at?->toISOString(),
                'questions_completed' => $session->questions_completed ?? [],
                'status' => $session->status,
                'progress_percentage' => $session->progress_percentage,
                'game' => [
                    'id' => $session->game->id,
                    'title' => $session->game->title,
                    'total_questions' => $totalQuestions
                ]
            ],
            'statistics' => [
                'total_questions' => $totalQuestions,
                'completed_questions' => $completedQuestions,
                'correct_answers' => $correctAnswers,
                'accuracy_percentage' => $completedQuestions > 0
                    ? round(($correctAnswers / $completedQuestions) * 100, 2)
                    : 0
            ],
            'badge' => $badge ? [
                'id' => $badge->id,
                'badge_name' => $badge->badge_name,
                'description' => $badge->description,
                'badge_image_path' => $badge->badge_image_path,
                'earned_at' => $badge->earned_at->toISOString()
            ] : null,
            'can_replay' => true // Always allow replay
        ];

        Log::info('Session results retrieved', [
            'session_id' => $sessionId,
            'student_id' => Auth::id(),
            'completed' => $session->isCompleted(),
            'has_badge' => $badge ? true : false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session results retrieved successfully',
            'data' => $sessionResults
        ]);

    } catch (\Exception $e) {
        Log::error('Failed to get session results', [
            'error' => $e->getMessage(),
            'session_id' => $sessionId,
            'student_id' => Auth::id(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to retrieve session results'
        ], 500);
    }
}
}
