<?php

namespace Illuminate\Console\Events;

enum ScheduledTaskSkipReason: string
{
    case Paused = 'paused';
    case Filtered = 'filtered';
}
