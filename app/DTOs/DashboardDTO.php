<?php

namespace App\DTOs;

class DashboardDTO
{
    public function __construct(
        public readonly array $metrics,
        public readonly array $projects,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            metrics: $data['metrics'],
            projects: $data['projects'],
        );
    }

    public function toArray(): array
    {
        return [
            'metrics' => $this->metrics,
            'projects' => $this->projects,
        ];
    }
}
