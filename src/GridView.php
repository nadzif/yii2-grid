<?php

namespace nadzif\grid;

//use kartik\export\ExportMenu;
use kartik\grid\GridView as KartikGridView;
use nadzif\grid\columns\SerialColumn;
use nadzif\grid\widgets\Select2;
use rmrevin\yii\fontawesome\FAS;
use rmrevin\yii\fontawesome\FontAwesome;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
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

    public $gridSize = [
        1   => 1,
        5   => 5,
        10  => 10,
        25  => 25,
        50  => 50,
        100 => 100,
        250 => 250,
    ];

    public function renderExport()
    {
//        parent::renderExport();
//        /** @var BaseGrid $filterModel */
//        $filterModel     = $this->filterModel;
//        $columns         = $filterModel->getColumns();
//        $exportedColumns = [['class' => SerialColumn::class]];
//        foreach ($columns as $column) {
//            if (!isset($column['class'])) {
//                $exportedColumns[] = $column;
//            }
//        }

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

        if ($this->export === false || !is_array($this->export)
            || empty($this->exportConfig)
            || !is_array($this->exportConfig)
        ) {
            return '';
        }
        $title       = $this->export['label'];
        $icon        = $this->export['icon'];
        $options     = $this->export['options'];
        $menuOptions = $this->export['menuOptions'];
        $iconPrefix  = $this->export['fontAwesome'] ? 'fas fa-' : 'glyphicon glyphicon-';
        $title       = ($icon == '') ? $title : "<i class='{$iconPrefix}{$icon}'></i> {$title}";
        if (!isset($this->_module->downloadAction)) {
            $action = ["/{$this->moduleId}/export/download"];
        } else {
            $action = (array)$this->_module->downloadAction;
        }
        $encoding    = ArrayHelper::getValue($this->export, 'encoding', 'utf-8');
        $bom         = ArrayHelper::getValue($this->export, 'bom', true);
        $target      = ArrayHelper::getValue($this->export, 'target', self::TARGET_POPUP);
        $formOptions = [
            'class'  => 'kv-export-form',
            'style'  => 'display:none',
            'target' => ($target == self::TARGET_POPUP) ? 'kvDownloadDialog' : $target,
        ];
        $form        = Html::beginForm($action, 'post', $formOptions) . "\n" .
            Html::hiddenInput('module_id', $this->moduleId) . "\n" .
            Html::hiddenInput('export_hash') . "\n" .
            Html::hiddenInput('export_filetype') . "\n" .
            Html::hiddenInput('export_filename') . "\n" .
            Html::hiddenInput('export_mime') . "\n" .
            Html::hiddenInput('export_config') . "\n" .
            Html::hiddenInput('export_encoding', $encoding) . "\n" .
            Html::hiddenInput('export_bom', $bom) . "\n" .
            Html::textarea('export_content') . "\n" .
            Html::endForm();
        $items       = empty($this->export['header']) ? [] : [$this->export['header']];
        foreach ($this->exportConfig as $format => $setting) {
            $iconOptions = ArrayHelper::getValue($setting, 'iconOptions', []);
            Html::addCssClass($iconOptions, $iconPrefix . $setting['icon']);
            $label  = (empty($setting['icon']) || $setting['icon'] == '')
                ? $setting['label']
                :
                Html::tag('i', '', $iconOptions) . ' ' . $setting['label'];
            $mime   = ArrayHelper::getValue($setting, 'mime', 'text/plain');
            $config = ArrayHelper::getValue($setting, 'config', []);
            if ($format === self::JSON) {
                unset($config['jsonReplacer']);
            }
            $dataToHash = $this->moduleId . $setting['filename'] . $mime . $encoding . $bom . Json::encode($config);
            $hash       = \Yii::$app->security->hashData($dataToHash, $this->_module->exportEncryptSalt);
            $items[]    = [
                'label'       => $label,
                'url'         => '#',
                'linkOptions' => [
                    'class'     => 'export-' . $format,
                    'data-mime' => $mime,
                    'data-hash' => $hash,
                ],
                'options'     => $setting['options'],
            ];
        }
        $itemsBefore = ArrayHelper::getValue($this->export, 'itemsBefore', []);
        $itemsAfter  = ArrayHelper::getValue($this->export, 'itemsAfter', []);
        $items       = ArrayHelper::merge($itemsBefore, $items, $itemsAfter);
        return \yii\bootstrap4\ButtonDropdown::widget(
                [
                    'label'       => $title,
                    'dropdown'    => ['items' => $items, 'encodeLabels' => false, 'options' => $menuOptions],
                    'options'     => $this->exportContainer,
                    'encodeLabel' => false,
                ]
            ) . $form;
    }

    protected function initLayout()
    {
        /** @var BaseGrid $filterModel */
        $filterModel = $this->filterModel;
        if ($filterModel->hasProperty('pageSize')) {
            $filterId = ArrayHelper::getValue($this->filterRowOptions, 'id');
            echo Html::beginTag('div', ['id' => $filterId]);
            echo Select2::widget([
                'model'        => $this->filterModel,
                'attribute'    => 'pageSize',
                'theme'        => Select2::THEME_DEFAULT,
                'hideSearch'   => true,
                'data'         => $this->gridSize,
                'options'      => ['class' => 'grid-size-filter'],
                'pluginEvents' => [
                    'change' => new JsExpression('function(e){$.pjax({container: \'#' . $this->id . '-pjax\'})}')
                ]
            ]);
            echo Html::endTag('div');;

        }

        parent::initLayout();
    }

    protected function registerAssets()
    {
        parent::registerAssets();
        $this->getView()->registerAssetBundle(GridViewAsset::className());
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