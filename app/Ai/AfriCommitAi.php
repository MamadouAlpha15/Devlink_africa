<?php

namespace App\Ai;

use App\Models\AiSearch;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * AfriCommit AI (cahier, section 18): understand a request written in plain French,
 * then recommend developers with a match percentage.
 */
class AfriCommitAi
{
    public function __construct(
        private ?ClaudeCriteriaExtractor $claude,
        private KeywordCriteriaExtractor $keywords,
        private DeveloperMatcher $matcher,
    ) {}

    public function search(User $user, string $query, int $limit = 10): AiSearch
    {
        [$criteria, $engine] = $this->understand($query);

        $results = $this->matcher->match($criteria, $limit)
            ->reject(fn (array $result) => $result['user']->is($user))
            ->values();

        return $user->aiSearches()->create([
            'query' => $query,
            'criteria' => $criteria->toArray() + ['engine' => $engine],
            'results' => $results->map(fn (array $result) => [
                'user_id' => $result['user']->id,
                'score' => $result['score'],
                'matched_skills' => $result['matched_skills'],
            ])->all(),
        ]);
    }

    /**
     * Claude first; the local analyser if Claude is not configured or fails.
     *
     * @return array{0: SearchCriteria, 1: string} The criteria and the engine used ("claude" or "local").
     */
    private function understand(string $query): array
    {
        if ($this->claude) {
            try {
                return [$this->claude->extract($query), 'claude'];
            } catch (CriteriaExtractionFailed $e) {
                Log::warning('AfriCommit AI: Claude unavailable, using the local analyser.', ['reason' => $e->getMessage()]);
            }
        }

        return [$this->keywords->extract($query), 'local'];
    }
}
