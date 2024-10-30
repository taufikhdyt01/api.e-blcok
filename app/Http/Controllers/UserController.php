<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Submission;
use App\Http\Resources\UserProfileResource;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function getProfile()
    {
        $user = User::findOrFail(auth()->id());

        $challengeStats = Submission::where('user_id', $user->id)
            ->select('challenge_id', 'status')
            ->groupBy('challenge_id', 'status')
            ->get();

        $totalChallenges = $challengeStats->unique('challenge_id')->count();
        $completedChallenges = $challengeStats->where('status', 'accepted')->unique('challenge_id')->count();

        $challengeHistory = DB::table('submissions as s1')
            ->join(DB::raw('(SELECT challenge_id, MAX(score) as max_score FROM submissions WHERE user_id = ' . $user->id . ' GROUP BY challenge_id) as s2'), function($join) {
                $join->on('s1.challenge_id', '=', 's2.challenge_id')
                    ->on('s1.score', '=', 's2.max_score');
            })
            ->where('s1.user_id', $user->id)
            ->select('s1.challenge_id', 's1.status', 's1.score', DB::raw('MIN(s1.submitted_at) as submitted_at'))
            ->groupBy('s1.challenge_id', 's1.status', 's1.score')
            ->orderBy('s1.score', 'desc')
            ->orderBy('submitted_at', 'asc')
            ->get();

        $challengeIds = $challengeHistory->pluck('challenge_id')->toArray();

        $challenges = DB::table('challenges')
            ->whereIn('id', $challengeIds)
            ->select('id', 'title', 'difficulty')
            ->get()
            ->keyBy('id');

        $totalScore = 0;
        $challengeHistory = $challengeHistory->map(function ($submission) use ($challenges, &$totalScore) {
            $challenge = $challenges[$submission->challenge_id] ?? null;
            $totalScore += $submission->score;
            return [
                'challenge_id' => $submission->challenge_id,
                'title' => $challenge ? $challenge->title : 'Unknown',
                'difficulty' => $challenge ? $challenge->difficulty : 'Unknown',
                'best_score' => $submission->score,
                'status' => $submission->status,
                'completed_at' => $submission->submitted_at,
            ];
        });

        $userData = [
            'user' => $user,
            'total_challenges' => $totalChallenges,
            'completed_challenges' => $completedChallenges,
            'total_score' => $totalScore,
            'challenge_history' => $challengeHistory,
        ];

        return response_success(
            'User profile retrieved successfully',
            new UserProfileResource($userData)
        );
    }
}