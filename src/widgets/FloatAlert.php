<?php
/**
 * Created by PhpStorm.
 * User: haruk
 * Date: 5/27/2018
 * Time: 10:03 PM
 */

namespace nadzif\grid\widgets;


use rmrevin\yii\fontawesome\FAS;
use rmrevin\yii\fontawesome\FontAwesome;
use yii\base\Widget;
use yii\bootstrap4\Alert;
use yii\helpers\Html;

class FloatAlert extends Widget
{

    const ALERT_SUCCESS = 'alert-success';
    const ALERT_DANGER  = 'alert-danger';
    const ALERT_INFO    = 'alert-info';
    const ALERT_WARNING = 'alert-warning';
    const COLOR_SOLID   = 'alert-solid';

    public $icon;
    public $title      = 'title';
    public $message    = 'message';
    public $options    = [];
    public $background = 'alert-success';
    public $time       = 3000;

    public function init()
    {
        parent::init();

        if ($this->icon === null) {
            $this->icon = FAS::icon(FontAwesome::_BELL);
        }

        $this->options = ['options' => $this->options];
    }

    public function run()
    {
        Alert::begin($this->options);
        echo Html::beginTag('div', ['class' => 'd-sm-flex align-items-center justify-content-start alert-content']);
        echo Html::beginTag('div', ['class' => 'alert-icon']);
        echo $this->icon;
        echo Html::endTag('div');
        echo Html::beginTag('div', ['class' => 'mg-sm-l-15 mg-t-15 mg-sm-t-0']);
        echo Html::tag('h5', $this->title, ['class' => 'mg-b-2 pd-t-2']);
        echo Html::tag('p', $this->message, ['class' => 'mg-b-0 tx-xs op-8']);
        echo Html::endTag('div');
        echo Html::endTag('div');
        echo Html::tag('div', '', ['class' => 'loading']);
        Alert::end();
    }
}