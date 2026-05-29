<?php

namespace App\Enums;

enum TaskDependencyTypeEnum: string
{
    case FINISH_TO_START = 'finish_to_start';
    case START_TO_START = 'start_to_start';
    case FINISH_TO_FINISH = 'finish_to_finish';
    case START_TO_FINISH = 'start_to_finish';

    public function label(): string
    {
        return match ($this) {
            self::FINISH_TO_START => 'Finish to Start',
            self::START_TO_START => 'Start to Start',
            self::FINISH_TO_FINISH => 'Finish to Finish',
            self::START_TO_FINISH => 'Start to Finish',
        };
    }
}
