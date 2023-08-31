<?php

namespace App\Services\DynamicForm\ModelInterface;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Интерфейс модели записи
 */
interface ItemModelInterface
{
    public function user(): BelongsTo;

    public function fields(): HasMany;

    public function actions(): HasMany;
}
