<?php

namespace pentajeu\yii\controllers;

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;
use yii\filters\VerbFilter;
use pentajeu\models\GalleryPhoto;

/**
 * Backend controller for GalleryManager widget.
 * Provides following features:
 *  - Image removal
 *  - Image upload/Multiple upload
 *  - Arrange images in gallery
 *  - Changing name/description associated with image
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'ajaxUpload' => ['post'],
                    'order' => ['post'],
                    'changeData' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Removes image with ids specified in post request.
     * On success returns 'OK'
     */
    public function actionDelete()
    {
        $id = Yii::$app->request->post('id');

        /** @var $photos GalleryPhoto[] */
        $photos = GalleryPhoto::find()->where(['id' => $id])->all();
        foreach ($photos as $photo) {
            if ($photo !== null) $photo->delete();
            else throw new BadRequestHttpException('Photo, not found');
        }
        echo 'OK';
    }

    /**
     * Method to handle file upload thought XHR2
     * On success returns JSON object with image info.
     * @param $gallery_id string Gallery Id to upload images
     * @throws CHttpException
     */
    public function actionAjaxUpload($gallery_id = null)
    {
        $model = new GalleryPhoto;
        $model->gallery_id = $gallery_id;
        $imageFile = UploadedFile::getInstanceByName('image');
        $model->file_name = $imageFile->name;
        $model->save();

        $model->setImage($imageFile->tempName);
        // not "application/json", because  IE8 trying to save response as a file
        header("Content-Type: text/html");
        echo Json::encode([
                'id' => $model->id,
                'rank' => $model->rank,
                'name' => (string)$model->name,
                'description' => (string)$model->description,
                'preview' => $model->getPreview(),
            ]);
    }

    /**
     * Saves images order according to request.
     * Variable $_POST['order'] - new arrange of image ids, to be saved
     * @throws CHttpException
     */
    public function actionOrder()
    {
        if (!isset(Yii::$app->request->post('order'))) throw new BadRequestHttpException('No data, to save');
        $gp = Yii::$app->request->post('order');
        $orders = array();
        $i = 0;
        foreach ($gp as $k => $v) {
            if (!$v) $gp[$k] = $k;
            $orders[] = $gp[$k];
            $i++;
        }
        sort($orders);
        $i = 0;
        $res = array();
        foreach ($gp as $k => $v) {
            /** @var $p GalleryPhoto */
            $p = GalleryPhoto::findOne($k);
            $p->rank = $orders[$i];
            $res[$k] = $orders[$i];
            $p->save(false);
            $i++;
        }

        echo Json::encode($res);
    }

    /**
     * Method to update images name/description via AJAX.
     * On success returns JSON array od objects with new image info.
     * @throws CHttpException
     */
    public function actionChangeData()
    {
        if (!isset(Yii::$app->request->post('photo'))) throw new BadRequestHttpException('Nothing, to save');
        $data = Yii::$app->request->post('photo');

        /** @var $models GalleryPhoto[] */
        $models = GalleryPhoto::find()->where(['id' => [array_keys($data)]])->indexBy('id')->all();
        foreach ($data as $id => $attributes) {
            if (isset($attributes['name']))
                $models[$id]->name = $attributes['name'];
            if (isset($attributes['description']))
                $models[$id]->description = $attributes['description'];
            $models[$id]->save();
        }
        $resp = array();
        foreach ($models as $model) {
            $resp[] = array(
                'id' => $model->id,
                'rank' => $model->rank,
                'name' => (string)$model->name,
                'description' => (string)$model->description,
                'preview' => $model->getPreview(),
            );
        }
        echo Json::encode($resp);
    }
}
