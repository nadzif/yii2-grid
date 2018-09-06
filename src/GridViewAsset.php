<?php
/**
 * Created by PhpStorm.
 * User: Nadzif Glovory
 * Date: 9/6/2018
 * Time: 2:04 PM
 */

namespace nadzif\grid;


use yii\web\AssetBundle;

class GridViewAsset extends AssetBundle
{
    public $sourcePath = "@nadzif/grid/assets";
    public $js         = [
    ];
    public $css        = [
        "css/grid.css"
    ];
    public $depends    = [
        "yii\web\YiiAsset"
    ];
}