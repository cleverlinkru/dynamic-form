<?php

namespace App\Services\DynamicForm\ModelInterface;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Интерфейс модели действия
 */
interface ActionModelInterface
{
    public function user(): BelongsTo;

    public function item(): BelongsTo;
}
