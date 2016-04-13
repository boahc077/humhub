<?php

namespace humhub\modules\notification\models;

use Yii;

/**
 * This is the model class for table "notification".
 *
 * @property integer $id
 * @property string $class
 * @property integer $user_id
 * @property integer $seen
 * @property string $source_class
 * @property integer $source_pk
 * @property integer $space_id
 * @property integer $emailed
 * @property string $created_at
 * @property integer $desktop_notified
 * @property integer $originator_user_id
 */
class Notification extends \humhub\components\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notification';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['class', 'user_id'], 'required'],
            [['user_id', 'seen', 'source_pk', 'space_id', 'emailed', 'desktop_notified', 'originator_user_id'], 'integer'],
            [['class', 'source_class'], 'string', 'max' => 100]
        ];
    }

    /**
     * @return \humhub\modules\notification\components\BaseNotification
     */
    public function getClass()
    {
        if (class_exists($this->class)) {
            $object = new $this->class;
            Yii::configure($object, [
                'source' => $this->getSourceObject(),
                'originator' => $this->originator,
                'record' => $this,
            ]);
            return $object;
        }
        return null;
    }

    public function getUser()
    {
        return $this->hasOne(\humhub\modules\user\models\User::className(), ['id' => 'user_id']);
    }

    public function getOriginator()
    {
        return $this->hasOne(\humhub\modules\user\models\User::className(), ['id' => 'originator_user_id']);
    }

    public function getSpace()
    {
        return $this->hasOne(\humhub\modules\space\models\Space::className(), ['id' => 'space_id']);
    }

    public function getSourceObject()
    {
        $sourceClass = $this->source_class;
        if (class_exists($sourceClass) && $sourceClass != "") {
            return $sourceClass::findOne(['id' => $this->source_pk]);
        }
        return null;
    }
    
    /**
     * Returns all available notifications of a module identified by its modulename.
     * 
     * @return array with format [moduleId => notifications[]]
     */
    public static function getModuleNotifications()
    {
        $result = [];
        foreach(Yii::$app->moduleManager->getModules(['includeCoreModules' => true]) as $module) {
            if($module instanceof \humhub\components\Module) {
                $notifications = $module->getNotifications();
                if(count($notifications) > 0) {
                    $result[$module->getName()] = $notifications;
                }
            }
        }
        return $result;
    }
    
    /**
     * Returns a distinct list of notification classes already in the database.
     */
    public static function getNotificationClasses()
    {
        return (new yii\db\Query())
        ->select(['class'])
        ->from(self::tableName())
        ->distinct()->all();
    }
    
    public static function findByUser($user, $limit = null, $maxId = null)
    {
        $userId = ($user instanceof \humhub\modules\user\models\User) ? $user->id : $user;
        
        $query = self::find();
        $query->andWhere(['user_id' => $userId]);
        
        if ($maxId != null && $maxId != 0) {
            $query->andWhere(['<', 'id', $maxId]);
        }
        
        if($limit != null && $limit > 0) {
            $query->limit($limit);
        }
        
        $query->orderBy(['seen' => SORT_ASC, 'created_at' => SORT_DESC]);
        
        return $query;
    }

}
