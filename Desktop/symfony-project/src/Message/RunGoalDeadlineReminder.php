<?php

namespace App\Message;

class RunGoalDeadlineReminder
{
    public function __construct(private ?int $userId = null) {}
    public function getUserId(): ?int { return $this->userId; }
}
