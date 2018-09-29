<?php

namespace nadzif\grid\columns;

use kartik\grid\ActionColumn as KartikActionColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

class ActionColumn extends KartikActionColumn
{

    public $action;
    public $keyName       = 'hashId';
    public $header        = '<i class="icon ion-navicon-round"></i>';
    public $width         = '120px';
    public $pjax          = false;
    public $updateOptions = ['label' => '<i class="btn btn-link text-info p-0 fas fa-edit"></i>'];
    public $deleteOptions = ['label' => '<i class="btn btn-link text-info p-0 fas fa-trash-alt"></i>'];
    public $ajax          = true;

    public function init()
    {
        parent::init();

        if (!$this->template && $this->ajax) {
            $this->template = '{update-ajax} {delete-ajax}';
        }

        $this->template = Html::tag('div', $this->template, ['class' => 'action-column']);
    }

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
                $js = new JsExpression("
                    const modal = $(this).parents('.gridview-wrapper').find('.update-form-action');
                    const form = modal.find('.modal-body form');

                    $.ajax({
                          url: '" . Url::to($url) . "',
                    }).done(function(data) {
                        form.attr('action', '" . Url::to($url) . "');
                    
                        if (typeof data === 'object') {
                            $.each(data, (name, value) => {
                                const input = form.find('[name=\"' + modal.data('model') + '[' + name + ']\"]');
                                
                                switch (input.prop('tagName')) {
                                    case 'INPUT':
                                    case 'SELECT':
                                        input.val(value).change();
                                        input.find('option').attr('selected', false);
                                        input.find('option[value=\"' + value + '\"]').attr('selected', true);
                                        break;
                                    case 'TEXTAREA':
                                        input.text(value);
                                        break;
                                }
                                
                                
                            });
                        }
                        modal.modal('show');
                    });
                ");
                return Html::button(false, [
                    'class'   => 'btn btn-link text-info p-0 ' . $icon,
                    'titile'  => $title,
                    'onclick' => $js
                ]);
            };
        } elseif ($name === 'delete-ajax') {
            $this->buttons[$name] = function ($url) use ($name, $title, $icon) {
                $confirmationMessage = \Yii::t('app', 'Are you sure want to delete this data?');

                return Html::a('<i class="' . $icon . '"></i>', '#', [
                    'title'      => $title,
                    'aria-label' => $title,
                    'class'      => 'btn btn-link text-info p-0',
                    'onclick'    => "
                                var a = this;
                                if (yii.confirm('$confirmationMessage')) {
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