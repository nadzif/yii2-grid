<?php

namespace nadzif\grid;

//use kartik\export\ExportMenu;
use kartik\grid\GridView as KartikGridView;
use nadzif\grid\columns\SerialColumn;
use rmrevin\yii\fontawesome\FAS;
use rmrevin\yii\fontawesome\FontAwesome;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Description of GridView
 *
 * @author Lambda
 */
class GridView extends KartikGridView
{
    const FILTER_DATE_RANGE = '\nadzif\grid\widgets\DateRangePicker';
    const FILTER_DATE       = '\nadzif\grid\widgets\DatePicker';
    const FILTER_SELECT2    = '\nadzif\grid\widgets\Select2';
    const ICON_ACTIVE       = '<span class="ion ion-checkmark text-success"></span>';
    const ICON_INACTIVE     = '<span class="ion ion-close text-danger"></span>';
    public $filterPosition   = KartikGridView::FILTER_POS_HEADER;
    public $bordered         = true;
    public $striped          = true;
    public $condensed        = true;
    public $hover            = true;
    public $pjax             = true;
    public $responsiveWrap   = true;
    public $export           = [
        'fontAwesome'      => false,
        'showConfirmAlert' => true,
        'target'           => '_blank',
        'icon'             => 'export'
    ];
    public $layout           = "<div class='grid-view-toolbar'>{toolbar}</div>{items}{pager}{summary}";
    public $resizableColumns = true;
    public $pager            = [
        'nextPageLabel'  => '<i class="fa fa-angle-right"></i>',
        'prevPageLabel'  => '<i class="fa fa-angle-left"></i>',
        'firstPageLabel' => '<i class="fa fa-angle-double-left"></i>',
        'lastPageLabel'  => '<i class="fa fa-angle-double-right"></i>',
    ];

    public function renderExport()
    {
        /** @var BaseGrid $filterModel */
        $filterModel     = $this->filterModel;
        $columns         = $filterModel->getColumns();
        $exportedColumns = [['class' => SerialColumn::class]];
        foreach ($columns as $column) {
            if (!isset($column['class'])) {
                $exportedColumns[] = $column;
            }
        }

//        return ExportMenu::widget([
//            'dataProvider'          => $this->dataProvider,
//            'columns'               => $exportedColumns,
//            'target'                => ExportMenu::TARGET_BLANK,
//            'batchSize'             => 1000,
//            'enableFormatter'       => true,
//            'fontAwesome'           => false,
//            'pjaxContainerId'       => 'kv-pjax-container',
//            'dropdownOptions'       => [
//                'label'       => \Yii::t('app', 'Export'),
//                'class'       => 'btn btn-custom-toolbar',
//                'itemsBefore' => [Html::tag('p', \Yii::t('app', 'Export All Data'))],
//            ],
//            'columnSelectorOptions' => ['class' => 'btn-custom-toolbar',]
//        ]);
    }

    protected function renderToolbar()
    {
        $toolbar = parent::renderToolbar();
        $toolbar .= Html::beginTag('div', ['class' => 'datatables-tools']);
        $toolbar .= Html::beginTag('div', ['id' => $this->id . '-filters', 'class' => 'select2-wrap']);
        $toolbar .= Html::endTag('div');;
        $filterId = ArrayHelper::getValue($this->filterRowOptions, 'id');

        if ($filterId) {
            $filterToggleButton =
                new JsExpression("(function () { $( \"#" . $this->id . "\" ).toggleClass(\"datatable-filters\"); })()");

            $toolbar .= Html::button(FAS::icon(FontAwesome::_FILTER), [
                'class'   => 'btn btn-info',
                'onclick' => $filterToggleButton
            ]);
        }

        $reloadPjaxJS =
            new JsExpression("(function () { $.pjax.reload({container:\"#" . $this->id . "-pjax\"}); })();");

        $toolbar .= Html::button(FAS::icon(FontAwesome::_SYNC), [
            'class'   => 'btn btn-info',
            'onclick' => $reloadPjaxJS
        ]);

        $toolbar .= Html::endTag('div');
        return $toolbar;
    }


}