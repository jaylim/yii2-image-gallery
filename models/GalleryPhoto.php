<?php

namespace pentajeu\yii\models;

use Yii;
use yii\db\ActiveRecord;
use yii\imagine\Image;

/**
 * This is the model class for table "gallery_photo".
 *
 * The followings are the available columns in table 'gallery_photo':
 * @property integer $id
 * @property integer $gallery_id
 * @property integer $rank
 * @property string $name
 * @property string $description
 * @property string $file_name
 *
 * The followings are the available model relations:
 * @property Gallery $gallery
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class GalleryPhoto extends ActiveRecord
{
    /** @var string Extensions for gallery images */
    public $galleryExt = 'jpg';
    /** @var string directory in web root for galleries */
    public $galleryDir = 'gallery';
    private $_sizes = array();

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        if ($this->db->tablePrefix !== null)
            return '{{gallery_photo}}';
        else
            return 'gallery_photo';

    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return [
            [['gallery_id', 'required']],
            [['name'], 'length', 'max' => 512],
            [['file_name'], 'length', 'max' => 128]
        ];
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'gallery' => array(self::BELONGS_TO, 'Gallery', 'gallery_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gallery_id' => 'Gallery',
            'rank' => 'Rank',
            'name' => 'Name',
            'description' => 'Description',
            'file_name' => 'File Name',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGallery()
    {
        return $this->hasOne(Gallery::className(), ['gallery_id' => 'id']);
    }

    public function save($runValidation = true, $attributes = null)
    {
        parent::save($runValidation, $attributes);
        if ($this->rank == null) {
            $this->rank = $this->id;
            $this->setIsNewRecord(false);
            $this->save(false);
        }
        return true;
    }

    public function getPreview()
    {
        return Yii::$app->request->baseUrl . '/' . $this->galleryDir . '/_' . $this->getFileName('') . '.' . $this->galleryExt;
    }

    private function getFileName($version = '')
    {
        return $this->id . $version;
    }

    public function getUrl($version = '')
    {
        return Yii::$app->request->baseUrl . '/' . $this->galleryDir . '/' . $this->getFileName($version) . '.' . $this->galleryExt;
    }

    public function setImage($path)
    {
        //save image in original size
        Image::frame($path, $margin=0)->save(Yii::getAlias("@webroot/{$this->galleryDir}/{$this->fileName}.{$this->galleryExt}"));
        //create image preview for gallery manager
        Image::frame($path, $margin)->resize(new Box(300, ))->save(Yii::getAlias("@webroot/{$this->galleryDir}/_{$this->fileName}.{$this->galleryExt}"));

        $this->updateImages();
    }

    public function delete()
    {
        $this->removeFile(Yii::getAlias("@webroot/{$this->galleryDir}/{$this->fileName}.{$this->galleryExt}"));
        $this->removeFile(Yii::getAlias("@webroot/{$this->galleryDir}/_{$this->fileName}.{$this->galleryExt}"));

        $this->removeImages();
        return parent::delete();
    }

    private function removeFile($fileName)
    {
        if (file_exists($fileName))
            @unlink($fileName);
    }

    public function removeImages()
    {
        foreach ($this->gallery->versions as $version => $actions) {
            $file_name = $this->getFileName($version);
            $this->removeFile(Yii::getAlias("@webroot/{$this->galleryDir}/{$file_name}.{$this->galleryExt}");
        }
    }

    /**
     * Regenerate image versions
     */
    public function updateImages()
    {
        foreach ($this->gallery->versions as $version => $actions) {
            $file_name = $this->getFileName($version);
            $this->removeFile(Yii::getAlias("@webroot/{$this->galleryDir}/{$file_name}.{$this->galleryExt}"));
            Image::frame(Yii::getAlias("@webroot/{$this->galleryDir}/_{$this->fileName}.{$this->galleryExt}"))->save(Yii::getAlias("@webroot/{$this->galleryDir}/{$file_name}.{$this->galleryExt}"));

            // $image = Yii::app()->image->load(Yii::getPathOfAlias('webroot') . '/' . $this->galleryDir . '/' . $this->getFileName('') . '.' . $this->galleryExt);
            // foreach ($actions as $method => $args) {
            //     call_user_func_array(array($image, $method), is_array($args) ? $args : array($args));
            // }
            // $image->save(Yii::getPathOfAlias('webroot') . '/' . $this->galleryDir . '/' . $this->getFileName($version) . '.' . $this->galleryExt);
        }
    }

    private function getSize($version = '')
    {
        if (!isset($this->_sizes[$version])) {
            $file_name = $this->getFileName($version);
            $path = Yii::getAlias("@webroot/{$this->galleryDir}/{$file_name}.{$this->galleryExt}");
            $this->_sizes[$version] = getimagesize($path);
        }
        return $this->_sizes[$version];
    }

    public function getWidth($version = '')
    {
        $s = $this->getSize($version);
        return $s[0];
    }

    public function getHeight($version = '')
    {
        $s = $this->getSize($version);
        return $s[1];
    }
}