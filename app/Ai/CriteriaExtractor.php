<?php

namespace App\Ai;

interface CriteriaExtractor
{
    /**
     * Turn a free-text request into search criteria.
     *
     * @throws CriteriaExtractionFailed when the extractor cannot answer (no API key, network, refusal…)
     */
    public function extract(string $query): SearchCriteria;
}
