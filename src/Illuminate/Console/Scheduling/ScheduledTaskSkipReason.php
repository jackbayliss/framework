<?php

namespace Illuminate\Console\Scheduling;

enum ScheduledTaskSkipReason: string
{
    case ConditionNotMet = 'condition_not_met';
    case Paused = 'paused';
    case RanOnAnotherServer = 'ran_on_another_server';

    public function description(): string
    {
        return match ($this) {
            self::ConditionNotMet => 'A when() or skip() condition prevented the task from running',
            self::Paused => 'Schedule paused',
            self::RanOnAnotherServer => 'Already ran on another server',
        };
    }
}
