<?php

namespace App\Services\DynamicForm\ModelInterface;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Интерфейс модели поля
 */
interface FieldModelInterface
{
    public function item(): BelongsTo;
}
