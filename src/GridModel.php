<?php

namespace nadzif\grid;

use kartik\select2\Select2;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Class GridModel
 *
 * @package common\base
 */
class GridModel extends ActiveRecord
{
    const FILTER_LIKE       = 'like';
    const FILTER_DATETIME   = 'datetime';
    const FILTER_DATE       = 'date';
    const FILTER_DATE_RANGE = 'dateRange';
    const FILTER_EQUAL      = 'equal';
    const FILTER_MORE_THAN  = 'moreThan';
    const FILTER_LESS_THAN  = 'lessThan';
    const FILTER_RELATION   = 'relation';
    const FILTER_LIST       = 'list';

    public $dropdownClass   = 'kartik\select2\Select2';
    public $dropdownOptions = [];
    public $dropdownItemKey;

    public $datePickerClass   = 'kartik\date\DatePicker';
    public $datePickerOptions = [];

    public $dateRangePickerClass   = 'kartik\daterange\DateRangePicker';
    public $dateRangePickerOptions = [];

    /** @var ActiveRecord */
    public $activeRecordModel;
    public $dateFilterFormat         = 'yyyy-mm-dd';
    public $dateRangeFilterSeparator = ' - ';

    public $joinWith = [];
    public $pageSize = 10;
    public $sortKey  = 'id';

    public $serialColumn        = true;
    public $serialColumnClass   = 'kartik\grid\SerialColumn';
    public $serialColumnOptions = [];

    public $checkboxColumn        = false;
    public $checkboxColumnClass   = 'kartik\grid\CheckboxColumn';
    public $checkboxColumnOptions = [];

    public $actionColumn        = true;
    public $actionColumnClass   = 'nadzif\grid\ActionColumn';
    public $actionColumnOptions = [];

    public $expandRowColumn        = false;
    public $expandRowColumnClass   = 'kartik\grid\ExpandRowColumn';
    public $expandRowColumnOptions = [];


    private $_activeRecord;
    private $_columns;
    private $_filters;

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

        if ($this->activeRecordModel) {
            $this->_activeRecord = new $this->activeRecordModel;
        }

        if ($this->dropdownClass == Select2::className()) {
            $this->dropdownOptions = ArrayHelper::merge([
                'theme'         => Select2::THEME_BOOTSTRAP,
                'pluginOptions' => ['allowClear' => true]
            ], $this->dropdownOptions);

            $this->dropdownItemKey = 'data';
        } else {
            $this->dropdownItemKey = 'items';
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


        $this->generateFilterRules();
    }

    private function generateFilterRules()
    {
        foreach ($this->filterRules() as $filterRule) {

            $attributes       = $filterRule[0];
            $attributesFilter = count($filterRule) == 1 ? self::FILTER_LIKE : $filterRule[1];
            $filterOptions    = [];

            if (count($filterRule) > 1) {
                foreach ($filterRule as $key => $value) {
                    if ($key != 0 || $key != 1) {
                        $filterOptions[$key] = $value;
                    }
                }
            }


            if (is_array($attributes)) {
                foreach ($attributes as $filterAttribute) {
                    $this->filterRule($filterAttribute, $attributesFilter, $filterOptions);
                }
            } else {
                $this->filterRule($attributes, $attributesFilter, $filterOptions);
            }

        }
    }

    public function filterRules()
    {
        return [[$this->getAttributesKey(), self::FILTER_LIKE]];
    }

    protected function getAttributesKey()
    {
        return array_keys($this->attributes);
    }

    private function filterRule($attribute, $filter, $filterOptions = [])
    {
        $attributeQuery = $this->queryRules()[$attribute];
        $attributeLabel = ArrayHelper::getValue($filterOptions, 'label', $this->getAttributeLabel($attribute));

        switch ($filter) {
            case self::FILTER_LIKE:
                $defaultFormat       = 'text';
                $condition           = ['like', $attributeQuery];
                $filterWidgetOptions = ['placeholder' => 'tes'];
                break;

            case self::FILTER_MORE_THAN:
                $defaultFormat = 'double';
                $condition     = ['>=', $attributeQuery];
                break;

            case self::FILTER_LESS_THAN:
                $defaultFormat = 'double';
                $condition     = ['<=', $attributeQuery];
                break;

            case self::FILTER_LIST:
                $defaultFormat       = 'text';
                $condition           = ['=', $attributeQuery];
                $filterType          = $this->dropdownClass;
                $filterWidgetOptions = ArrayHelper::merge(
                    ['options' => ['placeholder' => $attributeLabel]],
                    $this->dropdownOptions,
                    [$this->dropdownItemKey => $filterOptions['items']]
                );
                break;

            case self::FILTER_DATE_RANGE:
                $defaultFormat       = 'date';
                $filterType          = $this->dateRangePickerClass;
                $filterWidgetOptions = $this->dateRangePickerOptions;
                $condition           = ['between', $attributeQuery];
                break;

            case self::FILTER_DATE:
                $defaultFormat       = 'date';
                $filterType          = $this->datePickerClass;
                $filterWidgetOptions = $this->datePickerOptions;
                $condition           = ['=', $attributeQuery];
                break;

            case self::FILTER_DATETIME:
                $defaultFormat       = 'datetime';
                $filterType          = $this->datePickerClass;
                $filterWidgetOptions = $this->datePickerOptions;
                $condition           = ['between', $attributeQuery];
                break;

            default:
                $defaultFormat = 'text';
                $condition     = ['=', $attributeQuery];
                break;

        }

        $this->_filters[$attribute][] = $condition;

        $this->_columns[$attribute]['format']    = ArrayHelper::getValue($filterOptions, 'format', $defaultFormat);
        $this->_columns[$attribute]['label']     = $attributeLabel;
        $this->_columns[$attribute]['attribute'] = $attribute;

        $this->_columns[$attribute]['value'] = ArrayHelper::getValue($filterOptions, 'value', $attribute);
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

    /**
     * @return array
     *
     */
    public function queryRules()
    {
        $queryRules = [];
        foreach ($this->getAttributesKey() as $attributeKey) {
            $queryRules[$attributeKey] = $attributeKey;
        }

        return $queryRules;
    }

    public function rules()
    {
        return [
            ['pageSize', 'integer'],
            [$this->getAttributesKey(), 'safe'],
            [$this->getAttributesKey(), 'string'],
        ];
    }

    public function getDataProvider($searchQuery = false, $sort = [])
    {
        $query = $searchQuery ?: self::find();

        if ($this->joinWith) {
            $query->joinWith($this->joinWith);
        }

        $requestParams = \Yii::$app->request->queryParams;

        $this->load($requestParams);


        $dataProviderConfig = [
            'query'      => $query,
            'pagination' => ['pageSize' => $this->pageSize],
            'key'        => $this->sortKey
        ];

        if ($sort) {
            $dataProviderConfig['sort'] = $sort;
        }

        $dataProvider = new ActiveDataProvider($dataProviderConfig);


        if (!$this->validate()) {
            return $dataProvider;
        }

        foreach ($this->getColumns() as $attributeKey => $data) {
            if (isset($data['class'])) {
                continue;
            }

            foreach ($this->_filters[$attributeKey] as $filter) {

                /** @todo make more eficient */
                $dataProvider->sort->attributes[] = [
                    $attributeKey => [
                        'asc'  => [$attributeKey => SORT_ASC],
                        'desc' => [$attributeKey => SORT_DESC],
                    ]
                ];

                if ($filter[0] != 'between') {
                    $filter[] = $this->$attributeKey;
                } else {
                    $explodedDate = explode($this->dateRangeFilterSeparator, $this->$attributeKey);

                    if (count($explodedDate) == 2) {
                        $filter[] = $explodedDate[0];
                        $filter[] = $explodedDate[1];
                    } else {
                        if (preg_match('(\d{4}-\d{2}-\d{2})', $this->$attributeKey)) {
                            $filter[] = $this->$attributeKey . ' 00:00:00';
                            $filter[] = $this->$attributeKey . ' 23:59:59';
                        } else {
                            $filter = [$attributeKey => $this->$attributeKey];
                        }
                    }
                }

                $query->andFilterWhere($filter);
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
}