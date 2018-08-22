<?php

namespace nadzif\grid\widgets;

use kartik\grid\GridView as KartikGridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

/**
 * Description of GridView
 *
 * @author Lambda
 */
class GridView extends KartikGridView
{
    const FILTER_DATE_RANGE = '\app\base\DateRangePicker';
    const FILTER_DATE       = '\app\base\DatePicker';
    const FILTER_SELECT2    = '\app\base\Select2';
    const ICON_ACTIVE   = '<span class="ion ion-checkmark text-success"></span>';
    const ICON_INACTIVE = '<span class="ion ion-close text-danger"></span>';
    public $filterPosition = KartikGridView::FILTER_POS_HEADER;
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


}