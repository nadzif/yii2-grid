<?php

namespace nadzif\grid;

use http\Url;
use nadzif\grid\columns\ActionColumn;
use nadzif\grid\columns\CheckboxColumn;
use nadzif\grid\columns\ExpandRowColumn;
use nadzif\grid\columns\SerialColumn;
use nadzif\grid\widgets\DatePicker;
use nadzif\grid\widgets\DateRangePicker;
use nadzif\grid\widgets\Select2;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Class GridModel
 *
 * @package common\base
 */
class BaseGrid extends ActiveRecord
{
    const FILTER_LIKE       = 'like';
    const FILTER_EQUAL      = 'equal';
    const FILTER_DATE       = 'date';
    const FILTER_DATE_RANGE = 'dateRange';
    const FILTER_MORE_THAN  = 'moreThan';
    const FILTER_LESS_THAN  = 'lessThan';
    const FILTER_BETWEEN    = 'between';
    const FILTER_LIST       = 'list';
    const FILTER_LIST_AJAX  = 'listAjax';

    public $dropDownClass   = Select2::class;
    public $dropDownOptions = [];
    public $dropDownItemKey;

    public $datePickerClass   = DatePicker::class;
    public $datePickerOptions = [];

    public $dateRangePickerClass   = DateRangePicker::class;
    public $dateRangePickerOptions = [];

    public $dateFilterFormat         = 'yyyy-mm-dd';
    public $dateRangeFilterSeparator = ' - ';

    public $betweenSeparator = ' - ';

    public $joinWith = [];
    public $pageSize = 10;
    public $sortKey  = 'id';

    public $serialColumn        = true;
    public $serialColumnClass   = SerialColumn::class;
    public $serialColumnOptions = [];

    public $checkboxColumn        = false;
    public $checkboxColumnClass   = CheckboxColumn::class;
    public $checkboxColumnOptions = [];

    public $actionColumn        = true;
    public $actionColumnClass   = ActionColumn::class;
    public $actionColumnOptions = [];

    public  $expandRowColumn        = false;
    public  $expandRowColumnClass   = ExpandRowColumn::class;
    public  $expandRowColumnOptions = [];
    private $_columns;
    private $_filters;
    private $_queryRules            = [];

    /**
     * @param $key
     * @param $filterOption
     *
     * @throws InvalidConfigException
     */
    protected static function hasFilterOption($key, $filterOption)
    {
        if (ArrayHelper::keyExists($key, $filterOption)) {
            throw new InvalidConfigException();
        }
    }

    public function init()
    {
        parent::init();

        if ($this->dropDownClass == Select2::className()) {
            $this->dropDownOptions = ArrayHelper::merge([
                'theme'         => Select2::THEME_BOOTSTRAP,
                'pluginOptions' => ['allowClear' => true]
            ], $this->dropDownOptions);

            $this->dropDownItemKey = 'data';
        } else {
            $this->dropDownItemKey = 'items';
        }

        $this->datePickerOptions = ArrayHelper::merge([
            'pickerButton'  => ['label' => '<i class="fa fa-calendar"></i>'],
            'pluginOptions' => [
                'autoclose' => true,
                'format'    => $this->dateFilterFormat
            ]
        ], $this->dateRangePickerOptions);

        $this->dateRangePickerOptions = ArrayHelper::merge([
            'pluginOptions' => [
                'autoclose'     => true,
                'convertFormat' => true,
                'locale'        => [
                    'format'    => 'YYYY-MM-DD',
                    'separator' => $this->dateRangeFilterSeparator
                ]
            ]
        ], $this->dateRangePickerOptions);


        $this->generateGridRules();
    }

    private function generateGridRules()
    {
        foreach ($this->gridRules() as $gridRule) {
            $attributes = $gridRule[0];
            ArrayHelper::remove($gridRule, 0);

            $attributes = is_array($attributes) ? $attributes : [$attributes];

            foreach ($attributes as $filterAttribute) {
                $this->generateGridRule($filterAttribute, $gridRule);
            }

        }
    }

    public function gridRules()
    {
        return [[$this->getAttributesKey(), 'filter' => self::FILTER_LIKE]];
    }

    protected function getAttributesKey()
    {
        return array_keys($this->attributes);
    }

    private function generateGridRule($attribute, $columnOptions = [])
    {
        $attributeLabel = ArrayHelper::getValue($columnOptions, 'label', $this->getAttributeLabel($attribute));

        $filter      = ArrayHelper::getValue($columnOptions, 'filter', self::FILTER_LIKE);
        $filterQuery =
            ArrayHelper::getValue($columnOptions, 'filterQuery', $this->queryRelated($attribute, $this->tableName()));

        $this->_queryRules[$attribute] = $filterQuery;

        $queryParamAction = false;
        ArrayHelper::remove($columnOptions, 'filterQuery');

        if ($filter == self::FILTER_LIKE) {
            $defaultFormat       = 'text';
            $condition           = ['like', $filterQuery];
            $filterWidgetOptions = ['placeholder' => $attributeLabel];
        } elseif ($filter == self::FILTER_EQUAL) {
            $defaultFormat       = 'text';
            $condition           = ['=', $filterQuery];
            $filterWidgetOptions = ['placeholder' => $attributeLabel];
        } elseif ($filter == self::FILTER_LIST) {
            $defaultFormat       = 'text';
            $condition           = ['=', $filterQuery];
            $filterType          = $this->dropDownClass;
            $filterWidgetOptions = ArrayHelper::merge(
                ['options' => ['placeholder' => $attributeLabel]],
                $this->dropDownOptions,
                ArrayHelper::getValue($columnOptions, 'widgetOptions', []),
                [$this->dropDownItemKey => $columnOptions['items']]
            );
        } elseif ($filter == self::FILTER_DATE) {
            $defaultFormat       = 'date';
            $filterType          = $this->datePickerClass;
            $filterWidgetOptions = ArrayHelper::merge(
                ['options' => ['placeholder' => $attributeLabel]],
                $this->datePickerOptions,
                ArrayHelper::getValue($columnOptions, 'widgetOptions', [])
            );
            $condition           = ['=', $filterQuery];
        } elseif ($filter == self::FILTER_DATE_RANGE) {
            $defaultFormat       = 'date';
            $filterType          = $this->dateRangePickerClass;
            $filterWidgetOptions = ArrayHelper::merge(
                ['options' => ['placeholder' => $attributeLabel]],
                $this->dateRangePickerOptions,
                ArrayHelper::getValue($columnOptions, 'widgetOptions', [])
            );
            $condition           = ['between', $filterQuery];
            $queryParamAction    = ['explode', $this->dateRangeFilterSeparator];

        } elseif ($filter == self::FILTER_MORE_THAN) {
            $defaultFormat = 'double';
            $condition     = ['>=', $filterQuery];
        } elseif ($filter == self::FILTER_LESS_THAN) {
            $defaultFormat = 'double';
            $condition     = ['<=', $filterQuery];
        } elseif ($filter == self::FILTER_BETWEEN) {
            $defaultFormat    = 'decimal';
            $condition        = ['between', $filterQuery];
            $queryParamAction = ['explode', $this->betweenSeparator];
        } elseif ($filter == self::FILTER_LIST_AJAX) {
            $pluginOptions = [
                'allowClear'         => true,
                'minimumInputLength' => 3,
                'language'           => [
                    'errorLoading' => new JsExpression("function () { return 'Waiting for results...'; }"),
                ],
                'ajax'               => [
                    'url'      => Url::to(ArrayHelper::getValue($this->dropDownOptions, 'ajaxUrl')),
                    'dataType' => 'json',
                    'data'     => new JsExpression('function(params) { return {q:params.term}; }')
                ],
                'escapeMarkup'       => new JsExpression('function (markup) { return markup; }'),
                'templateResult'     => new JsExpression('function(city) { return city.text; }'),
                'templateSelection'  => new JsExpression('function (city) { return city.text; }'),
            ];

            ArrayHelper::remove($this->dropDownOptions, 'ajaxUrl');

            $defaultFormat       = 'text';
            $condition           = ['=', $filterQuery];
            $filterType          = $this->dropDownClass;
            $filterWidgetOptions = ArrayHelper::merge(
                ['options' => ['placeholder' => $attributeLabel]],
                $this->dropDownOptions,
                ArrayHelper::getValue($columnOptions, 'widgetOptions', []),
                ['pluginOptions' => $pluginOptions]
            );
        }

        $this->_filters[$attribute]['condition']        = $condition;
        $this->_filters[$attribute]['queryParamAction'] = $queryParamAction;

        $this->_columns[$attribute]['format']    = ArrayHelper::getValue($columnOptions, 'format', $defaultFormat);
        $this->_columns[$attribute]['label']     = $attributeLabel;
        $this->_columns[$attribute]['attribute'] = $attribute;

        $this->_columns[$attribute]['value'] = ArrayHelper::getValue($columnOptions, 'value', $attribute);
        if ($this->_columns[$attribute]['format'] == 'phone') {
            $this->_columns[$attribute]['format'] = 'html';

            $this->_columns[$attribute]['value'] = function ($model) use ($attribute) {
                return Html::a($model->$attribute, 'tel:' . $model->$attribute);
            };
        }

        if (isset($filterType) && isset($filterWidgetOptions)) {
            $this->_columns[$attribute]['filterType']          = $filterType;
            $this->_columns[$attribute]['filterWidgetOptions'] = $filterWidgetOptions;
        }
    }

    public function queryRelated($column, $tableName)
    {
        return $tableName . '.' . $column;
    }

    public function rules()
    {
        return [
            ['pageSize', 'integer'],
            [$this->getAttributesKey(), 'safe'],
            [$this->getAttributesKey(), 'string'],
        ];
    }

    public function getDataProvider($searchQuery = false)
    {

        $query = $searchQuery ?: self::find();

        if ($this->joinWith) {
            $query->joinWith($this->joinWith);
        }

        $requestParams = \Yii::$app->request->queryParams;

        $this->load($requestParams);

        $activeDataProviderConfig = [
            'query'      => $query,
            'pagination' => ['pageSize' => $this->pageSize],
            'key'        => $this->sortKey
        ];

        if ($this->hasAttribute('createdAt')) {
            $activeDataProviderConfig['sort'] = ['defaultOrder' => ['createdAt' => SORT_DESC]];

        }

        $dataProvider = new ActiveDataProvider($activeDataProviderConfig);

        if (!$this->validate()) {
            return $dataProvider;
        }


        foreach ($this->_columns as $attributeKey => $data) {
            if (isset($data['class'])) {
                continue;
            }

            foreach ($this->_filters[$attributeKey] as $filter) {
                $filterCondition  = ArrayHelper::getValue($filter, 'condition');
                $queryParamAction = ArrayHelper::getValue($filter, 'queryParamsAction', false);

                $dataProvider->sort->attributes[$attributeKey] = [
                    'asc'  => [$this->_queryRules[$attributeKey] => SORT_ASC],
                    'desc' => [$this->_queryRules[$attributeKey] => SORT_DESC],
                ];

                if ($queryParamAction) {
                    if ($queryParamAction[0] == 'explode') {
                        $explodedDate = explode($queryParamAction[1], $this->$attributeKey);

                        if (count($explodedDate) == 2) {
                            $filterCondition[] = $explodedDate[0];
                            $filterCondition[] = $explodedDate[1];
                        } else {
                            $filterCondition = [$queryParamAction => $this->$attributeKey];
                        }
                    }
                } else {
                    $filterCondition[] = $this->$attributeKey;
                }

                $query->andFilterWhere($filterCondition);
            }
        }

        return $dataProvider;
    }

    public function getColumns()
    {
        $columns = [];

        if ($this->serialColumn) {
            $columns[] = ArrayHelper::merge(['class' => $this->serialColumnClass], $this->serialColumnOptions);
        }

        if ($this->checkboxColumn) {
            $columns[] = ArrayHelper::merge(['class' => $this->checkboxColumnClass], $this->checkboxColumnOptions);
        }

        if ($this->_columns) {
            $columns += $this->_columns;
        } else {
            $columns += $this->getAttributesKey();
        }

        if ($this->actionColumn) {
            $columns[] = ArrayHelper::merge(['class' => $this->actionColumnClass], $this->actionColumnOptions);
        }

        if ($this->expandRowColumn) {

            $defaultExpandRowColumnOptions = [
                'expandIcon'              => '<span class="fas fa-expand text-info"></span>',
                'collapseIcon'            => '<span class="fas fa-compress text-info"></span>',
                'detailAnimationDuration' => 0,
                'value'                   => function ($model, $key, $index, $column) {
                    return GridView::ROW_COLLAPSED;
                }
            ];

            $columns[] = ArrayHelper::merge(
                ['class' => $this->expandRowColumnClass],
                $defaultExpandRowColumnOptions,
                $this->expandRowColumnOptions
            );
        }


        return $columns;
    }

    public function getFilters()
    {
        return $this->_filters;
    }

    public function queryCount($column, $tableName = null, $condition = [], $fullSelect = false, $db = null)
    {
        return $this->queryColumn('COUNT', $column, $tableName, $condition, $fullSelect, $db);
    }

    private function queryColumn($scalar, $column, $tableName = null, $condition = [], $fullSelect = false, $db = null)
    {
        if ($tableName === null) {
            $tableName = $this->tableName();
        }

        if ($fullSelect) {
            $query = (new Query())->select($scalar . '(' . $column . ')')
                ->from($tableName)
                ->where($condition)
                ->createCommand($db);

            return $query->getRawSql();
        } else {
            $query = $scalar . '(' . $tableName . '.' . $column . ')';
        }

        return '(' . $query . ')';
    }

    public function queryConcat($a, $b)
    {
        $args = func_get_args();

        $query = 'CONCAT(';

        foreach ($args as $index => $arg) {
            $query .= $index == 0 ? '\'' . $arg . '\'' : ',\'' . $arg . '\'';
        }

        return $query . ')';
    }

    public function querySum($column, $tableName = null, $condition = [], $fullSelect = false, $db = null)
    {
        return $this->queryColumn('SUM', $column, $tableName, $condition, $fullSelect, $db);
    }

}