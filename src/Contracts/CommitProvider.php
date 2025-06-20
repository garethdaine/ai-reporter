<?php

namespace AIReporter\Contracts;

use DateTimeInterface;

interface CommitProvider
{
    /**
     * Return a formatted commit log between two dates (inclusive).
     * Implementation decides the exact string format
     * (e.g. "- <hash> <message> (<author>)").
     */
    public function between(
        DateTimeInterface $start,
        DateTimeInterface $end
    ): string;

    /**
     * Return a directory snapshot (tree-style text) limited to a depth.
     */
    public function treeSnapshot(int $maxDepth = 3): string;
}
