<?php

namespace App\Services\DynamicForm;

use App\Services\DynamicForm\Config\Config;
use App\Services\DynamicForm\EditRules\EditRule;
use App\Services\DynamicForm\ValidRules\RequiredValidRule;
use App\Services\DynamicForm\ValidRules\UniqueValidRule;
use App\Services\DynamicForm\ValidRules\ValidRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * Основной объект сервиса динамических форм. Позволяет работать с записями: создавать, редактировать, удалять и т.д.
 * Набор полей каждой записи определяется конфигурацией, которая может меняться.
 * Работает с записями. Каждая запись состоит из набора полей. Всё, что происходит с записью, фиксируется как действия.
 */
class DynamicForm
{
    /**
     * Создание объекта динамической формы
     *
     * @param  Config  $config
     * @param  string  $itemModelClass
     * @param  string  $fieldModelClass
     * @param  string  $actionModelClass
     */
    public function __construct(
        public Config $config,
        public string $itemModelMapperClass,
        public string $actionModelClass
    ) {
        $this->addValidRule('required', new RequiredValidRule());
        $this->addValidRule('unique', new UniqueValidRule());
    }

    public function getItemModelMapper($model = null)
    {
        return new $this->itemModelMapperClass($model);
    }

    /**
     * Дополнение запроса при получении действий
     *
     * @param $query
     * @return void
     */
    public function actionQuery($query)
    {
    }

    /**
     * Добавление правила проверки ввода значений полей
     *
     * @param  string  $name
     * @param  ValidRule  $rule
     * @return void
     */
    public function addValidRule(string $name, ValidRule $rule)
    {
        $this->validRulesMap[$name] = $rule;
    }

    /**
     * Получение правила проверки ввода значений полей
     *
     * @param  string  $name
     * @return ValidRule|null
     */
    public function getValidRule(string $name): ?ValidRule
    {
        return isset($this->validRulesMap[$name]) ? $this->validRulesMap[$name] : null;
    }

    /**
     * Добавление правила доступности редактирования полей
     *
     * @param  string  $name
     * @param  EditRule  $editRule
     * @return void
     */
    public function addEditRule(string $name, EditRule $editRule)
    {
        $this->editRulesMap[$name] = $editRule;
    }

    /**
     * Получение правила доступности редактирования полей
     *
     * @param  string  $name
     * @return EditRule|null
     */
    public function getEditRule(string $name): ?EditRule
    {
        return isset($this->editRulesMap[$name]) ? $this->editRulesMap[$name] : null;
    }

    /**
     * Получение записей
     *
     * @param  array  $params
     * @return array
     */
    public function getItems(array $params)
    {
        $itemModelMapper = $this->getItemModelMapper();

        $query = $itemModelMapper->query();

        if (isset($params['search']) && $params['search']) {
            $search = mb_strtolower($params['search']);
            $itemModelMapper->search($query, $search);
        }

        $this->itemsFilter($query, $params);
        $this->itemsSort($query, $params);
        [$models, $total] = $this->itemsGet($query, $params);

        $items = [];
        foreach ($models as $model) {
            $items[] = Item::getInstanceWithModel($this, $model);
        }

        return [$items, $total];
    }

    /**
     * Получение действий для всех записей или для отдельной
     *
     * @param  int|null  $itemId
     * @return array
     */
    public function getActions(?string $itemId = null): array
    {
        $query = $this->actionModelClass::query()
            ->when($itemId, function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            })
            ->orderByDesc('created_at')
            ->limit(10);

        $this->actionQuery($query);

        $models = $query->get();

        $actions = [];
        foreach ($models as $model) {
            $actions[] = Action::getInstanceWithModel($this, $model);
        }

        return $actions;
    }


    protected $validRulesMap = [];

    protected $editRulesMap = [];

    /**
     * Добавление условий фильтрации к запросу записей
     *
     * @param  Builder  $query
     * @param  array  $params
     * @return void
     * @throws \Exception
     */
    protected function itemsFilter(Builder $query, array $params)
    {
        if (!isset($params['filter']) || !$params['filter']) {
            return;
        }

        $itemModelMapper = $this->getItemModelMapper();
        foreach ($this->config->getFields() as $configField) {
            $fieldClass = Field::getClass($configField);
            if ($fieldClass::$filterable && isset($params['filter'][$configField['name']])) {
                $fieldClass::handleFilter(
                    $query,
                    $configField,
                    $itemModelMapper,
                    $params['filter'][$configField['name']]
                );
            }
        }
    }

    /**
     * Добавление сортировки к запросу записей
     *
     * @param  Builder  $query
     * @param  array  $params
     * @return void
     * @throws \Exception
     */
    protected function itemsSort(Builder $query, array $params)
    {
        $orderDir = false;
        if (isset($params['orderDir']) && $params['orderDir'] && $params['orderDir'] == 'asc') {
            $orderDir = true;
        }

        $itemModelMapper = $this->getItemModelMapper();
        if (isset($params['orderBy']) && $params['orderBy']) {
            if ($params['orderBy'] == 'created_at') {
                $itemModelMapper->order($query, 'created_at', $orderDir);
            } else {
                $configField = $this->config->getField($params['orderBy']);
                if ($configField && Field::getClass($configField)::$sortable) {
                    $itemModelMapper->order($query, $configField['name'], $orderDir);
                }
            }
        } else {
            $itemModelMapper->order($query, 'id', $orderDir);
        }
    }

    /**
     * Получение моделей записей с пагинацией или без
     *
     * @param  Builder  $query
     * @param  array  $params
     * @return array
     */
    protected function itemsGet(Builder $query, array $params)
    {
        if (isset($params['perPage']) && $params['perPage']) {
            $models = $query->paginate($params['perPage']);
            $total = $models->total();
            $models = $models->items();
        } else {
            $models = $query->get();
            $total = count($models);
        }

        return [$models, $total];
    }
}
