<?php

namespace pentajeu\yii\widgets\gallery;

use Yii;
use yii\base\Widget;
use yii\helpers\Json;

/**
 * Widget to manage gallery.
 * Requires Twitter Bootstrap styles to work.
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryManager extends Widget
{
    /** @var Gallery Model of gallery to manage */
    public $gallery;
    public $htmlOptions = [];


    /** Render widget */
    public function run()
    {
        $view = $this->getView();
        GalleryAsset::register($view);

        echo $this->hasModel()
            ? Html::activeTextarea($this->model, $this->attribute, $this->options)
            : Html::textarea($this->name, $this->value, $this->options);
        $clientOptions = empty($this->clientOptions)
            ? null
            : Json::encode($this->clientOptions);

        $photos = array();
        foreach ($this->gallery->galleryPhotos as $photo) {
            $photos[] = array(
                'id' => $photo->id,
                'rank' => $photo->rank,
                'name' => (string)$photo->name,
                'description' => (string)$photo->description,
                'preview' => $photo->getPreview(),
            );
        }

        $opts = array(
            'hasName' => $this->gallery->name ? true : false,
            'hasDesc' => $this->gallery->description ? true : false,
            'uploadUrl' => Yii::$app->urlManager->createUrl([Yii::$app->controller->id . '/ajaxUpload', 'gallery_id' => $this->gallery->id]),
            'deleteUrl' => Yii::$app->urlManager->createUrl([Yii::$app->controller->id . '/delete']),
            'updateUrl' => Yii::$app->urlManager->createUrl([Yii::$app->controller->id . '/changeData']),
            'arrangeUrl' => Yii::$app->urlManager->createUrl([Yii::$app->controller->id . '/order']),
            'nameLabel' => Yii::t('galleryManager.main', 'Name'),
            'descriptionLabel' => Yii::t('galleryManager.main', 'Description'),
            'photos' => $photos,
        );

        if (Yii::$app->request->enableCsrfValidation) {
            $opts['csrfTokenName'] = Yii::$app->request->csrfParam;
            $opts['csrfToken'] = Yii::$app->request->csrfToken;
        }


        $opts = Json::encode($opts);
        $view->registerJs('jQuery( "#' . $this->id . '" ).galleryManager(' . $opts . ');');

        $this->htmlOptions['id'] = $this->id;
        $this->htmlOptions['class'] = 'GalleryEditor';

        $this->render('galleryManager');
    }

}
