<?php
/**
 * 小米推送通知
 */

namespace XMPush;

use XMPush\Exceptions\NotificationException;

class Notification
{
    /**
     * 小米应用对应的id
     *
     * @var null
     */
    private $_appId = null;

    /**
     * 小米应用对应的key
     *
     * @var null
     */
    private $_appKey = null;

    /**
     * 小米应用对应的密钥
     *
     * @var null
     */
    private $_appSecret = null;

    /**
     * 推送使用的环境
     *
     * @var null
     */
    private $_env = null;


    /**
     * 小米应用对应的报名
     *
     * @var null
     */
    private $_appPackage = null;

    /**
     * 接收推送的系统类型
     *
     * iphone android
     *
     * @var null
     */
    private $_systemType = null;

    /**
     * Notification constructor.
     *
     * @param string $appId
     * @param string $appKey
     * @param string $appSecret
     * @param string $appPackage
     * @param string $env official 或 sandbox
     * @param string $systemType ios 或 android
     */
    public function __construct($appId, $appKey, $appSecret, $appPackage, $env, $systemType)
    {
        $this->_appId = $appId;
        $this->_appKey = $appKey;
        $this->_appSecret = $appSecret;
        $this->_appPackage = $appPackage;
        $this->_env = $env;
        $this->_systemType = $systemType;
    }

    /**
     * 通知到指定的 regid(每个设备的独立标识)
     *
     * @param string $regId
     * @param string $title
     * @param string $desc
     * @param string $payload
     * @param null|string $notifyId
     * @return boolean
     */
    public function toRegId($regId, $title, $desc, $payload, $notifyId = null)
    {
        //检查必要的初始值
        $this->_validateInit();

        Constants::setSecret($this->_appSecret);
        Constants::setPackage($this->_appPackage);

        if ($this->_systemType === 'android') {
            $message = $this->_androidBuilder($title, $desc, $payload, $notifyId);
        } else {
            $message = $this->_iosBuilder($title, $desc, $payload, $notifyId);
        }

        $sender = new Sender();
        $result = $sender->send($message, $regId);
        $resultMessageId = $result->getMessageId();
        if ($resultMessageId == 'null') {//失败
            //todo 日志系统
            $errorCode   = $result->getErrorCode();
            $errorReason = $result->getReason();
            return false;
        }
        return true;
    }

    /**
     * 通知到指定的 regid数组(每个设备的独立标识)
     *
     * @param array $regIds
     * @param string $title
     * @param string $desc
     * @param string $payload
     * @param null|string $notifyId
     * @return boolean
     */
    public function toRegIds($regIds, $title, $desc, $payload, $notifyId = null)
    {
        //检查必要的初始值
        $this->_validateInit();

        Constants::setSecret($this->_appSecret);
        Constants::setPackage($this->_appPackage);

        if ($this->_systemType === 'android') {
            $message = $this->_androidBuilder($title, $desc, $payload, $notifyId);
        } else {
            $message = $this->_iosBuilder($title, $desc, $payload, $notifyId);
        }

        $sender = new Sender();
        $result = $sender->sendToIds($message, $regIds);
        $resultMessageId = $result->getMessageId();
        if ($resultMessageId == 'null') {//失败
            //todo 日志系统
            $errorCode   = $result->getErrorCode();
            $errorReason = $result->getReason();
            return false;
        }
        return true;
    }

    /**
     * 向所有设备群发
     *
     * @param string $title
     * @param string $desc
     * @param string $payload
     * @param null|string $notifyId
     * @return bool
     */
    public function broadcastAll($title, $desc, $payload, $notifyId = null)
    {
        //检查必要的初始值
        $this->_validateInit();

        Constants::setSecret($this->_appSecret);
        Constants::setPackage($this->_appPackage);

        if ($this->_systemType === 'android') {
            $message = $this->_androidBuilder($title, $desc, $payload, $notifyId);
        } else {
            $message = $this->_iosBuilder($title, $desc, $payload, $notifyId);
        }

        $sender = new Sender();
        $result = $sender->broadcastAll($message);
        $resultMessageId = $result->getMessageId();
        if ($resultMessageId == 'null') {//失败
            //todo 日志系统
            $errorCode   = $result->getErrorCode();
            $errorReason = $result->getReason();
            return false;
        }
        return true;
    }

    /**
     * 检查必要的初始值
     *
     * @throws NotificationException
     */
    private function _validateInit()
    {
        if (is_null($this->_appId) || is_null($this->_appKey) || is_null($this->_appSecret) ||
            is_null($this->_appPackage) || is_null($this->_env) || is_null($this->_systemType)
        ) {
            throw new NotificationException('应用参数未定义');
        }
        if ($this->_env != 'official' && $this->_env != 'sandbox') {
            throw new NotificationException('_env参数定义错误');
        }
        if ($this->_systemType != 'iphone' && $this->_systemType != 'android') {
            throw new NotificationException('_systemType参数定义错误');
        }

        if ($this->_env == 'official') {
            Constants . useOfficial();
        } else {
            Constants . useSandbox();
        }
    }

    /**
     * 构建android
     *
     * @param $title
     * @param $desc
     * @param $payload
     * @param null $notifyId
     */
    private function _androidBuilder($title, $desc, $payload, $notifyId = null)
    {
        $message = new Builder();
        $message->title($title);//标题
        $message->description($desc);//内容
        $message->passThrough(0);//0=通知栏消息 1=透传
        $message->payload($payload); // 对于预定义点击行为，payload会通过点击进入的界面的intent中的extra字段获取，而不会调用到onReceiveMessage方法。
        $message->extra(Builder::notifyEffect, 1); // 此处设置预定义点击行为，1为打开app
        $message->extra(Builder::notifyForeground,1);
        if (is_null($notifyId)) {
            $message->notifyId(microtime(true) * 10000);
        } else {
            $message->notifyId($notifyId);
        }
        $message->build();
        return $message;
    }

    private function _iosBuilder($title, $desc, $payload, $notifyId = null)
    {
        //todo
    }
}

