<?php

namespace pentajeu\yii\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "gallery".
 *
 * The followings are the available columns in table 'gallery':
 * @property integer $id
 * @property string $versions_data
 * @property integer $name
 * @property integer $description
 *
 * The followings are the available model relations:
 * @property GalleryPhoto[] $galleryPhotos
 *
 * @property array $versions Settings for image auto-generation
 * @example
 *  array(
 *       'small' => array(
 *              'resize' => array(200, null),
 *       ),
 *      'medium' => array(
 *              'resize' => array(800, null),
 *      )
 *  );
 *
 *
 * @author Bogdan Savluk <savluk.bogdan@gmail.com>
 */
class Gallery extends ActiveRecord
{
    private $_versions;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        if ($this->db->tablePrefix !== null)
            return '{{gallery}}';
        else
            return 'gallery';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'description'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGalleryPhotos()
    {
        return $this->hasMany(GalleryPhoto::className(), ['id' => 'gallery_id'])->orderBy('`rank` ASC');
    }

    public function getVersions()
    {
        if (empty($this->_versions)) $this->_versions = unserialize($this->versions_data);
        return $this->_versions;
    }

    public function setVersions($value)
    {
        $this->_versions = $value;
    }

    public function beforeSave()
    {
        if (!empty($this->_versions))
            $this->versions_data = serialize($this->_versions);
        return parent::beforeSave();
    }

    public function delete()
    {
        foreach ($this->galleryPhotos as $photo) {
            $photo->delete();
        }
        return parent::delete();
    }
}