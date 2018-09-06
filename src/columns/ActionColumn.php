<?php

namespace nadzif\grid;

use kartik\grid\ActionColumn as KartikActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

class ActionColumn extends KartikActionColumn
{
    public $template      = '<div class="action-column">{update-ajax} {delete-ajax}</div>';
    public $action;
    public $keyName       = 'hashId';
    public $header        = '<i class="icon ion-navicon-round"></i>';
    public $width         = '120px';
    public $pjax          = false;
    public $updateOptions = ['label' => '<i class="btn btn-link text-info p-0 fas fa-edit"></i>'];
    public $deleteOptions = ['label' => '<i class="btn btn-link text-info p-0 fas fa-trash-alt"></i>'];

    public function createUrl($action, $model, $key, $index)
    {
        if ($this->action) {
            $action = $this->action . '-' . $action;
        }

        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
        }

        $params    = is_array($key) ? $key : [$this->keyName => (string)$key];
        $params[0] = $this->controller ? $this->controller . '/' . $action : $action;

        return Url::toRoute($params);
    }

    protected function initDefaultButtons()
    {
        parent::initDefaultButtons();
        $this->setDefaultButton('update-ajax', \Yii::t('app', 'Update'), 'fas fa-edit');
        $this->setDefaultButton('delete-ajax', \Yii::t('app', 'Delete'), 'fas fa-trash-alt');
    }

    protected function setDefaultButton($name, $title, $icon)
    {
        if ($name === 'update-ajax') {
            $this->buttons[$name] = function ($url) use ($name, $title, $icon) {
                return Html::button(false, [
                    'class'   => 'btn btn-link text-info p-0 ' . $icon,
                    'titile'  => $title,
                    'onclick' => new JsExpression(
                        '(function(e){
                            console.log(e)
                        })()
                        $.ajax({
                          url: \'' . Url::to($url) . '\',
                        }).done(function(html) {
                          var a = $(\'#grid-update-section\').html(html);
                          $(\'#grid-update-section\').find(\'.modal\').modal(\'show\');
                        });'
                    )
                ]);
            };
        } elseif ($name === 'delete-ajax') {
            $this->buttons[$name] = function ($url) use ($name, $title, $icon) {
                $confirmationMessage = \Yii::t('app', 'Are you sure want to delete this data?');

                return Html::a('<i class="' . $icon . '"></i>', '#', [
                    'title'      => $title,
                    'aria-label' => $title,
                    'onclick'    => "
                                var a = this;
                                if (confirm('$confirmationMessage')) {
                                    $.ajax('$url', {
                                        type: 'POST'
                                    }).done(function(data) {
                                    var id = $(a).parents('[data-pjax-container]').attr('id');
                                        $.pjax.reload({container: '#'+id});
                                        window.FloatAlert.renderAlert(data);
                                    });
                                }
                                return false;
                            ",
                ]);
            };
        }


        return parent::setDefaultButton($name, $title, $icon);
    }

}