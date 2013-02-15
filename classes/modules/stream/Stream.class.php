<?php

class PluginSecretblog_ModuleStream extends PluginSecretblog_Inherit_ModuleStream {

  protected function isSecredBlog($iBlogId) {
    $oBlog = $this->Blog_GetBlogById($iTargetId);
    if($oBlog and $oBlog->getType() == 'secret') {
      return true;
    }  
  }

  protected function isInSecretBlog($sEventType, $iTargetId) {
    $aType = $this->aEventTypes[$sEventType];
    
    if ($aType['related'] == 'blog') {
      return $this->isSecredBlog($iTargetId);
    } else if($aType['related'] == 'topic') {
      $oTopic = $this->Topic_GetTopicById($iTargetId);
      if ($oTopic) {
        return $this->isSecredBlog($oTopic->getBlogId());
      }
    } else if($aType['related'] == 'comment') {
      $oComment = $this->Comment_GetCommentById($iTargetId);
      if($oComment->getTargetType() == 'topic') {
        return $this->isSecredBlog($oTopic->getBlogId());
      }      
    }
    return false;
  }

  /**
   * Запись события в ленту
   *
   * @param int $iUserId	ID пользователя
   * @param string $sEventType	Тип события
   * @param int $iTargetId	ID владельца
   * @param int $iPublish	Статус
   * @return bool
   */
  public function Write($iUserId, $sEventType, $iTargetId, $iPublish = 1) {
    $iPublish = (int) $iPublish;
    if (!$this->IsAllowEventType($sEventType)) {
      return false;
    }
    if ($this->isInSecretBlog($sEventType, $iTargetId)) {
      return false;
    }

    $aParams = $this->aEventTypes[$sEventType];
    if (isset($aParams['unique']) and $aParams['unique']) {
      /**
       * Проверяем на уникальность
       */
      if ($oEvent = $this->GetEventByTarget($sEventType, $iTargetId)) {
        /**
         * Событие уже было
         */
        if ($oEvent->getPublish() != $iPublish) {
          $oEvent->setPublish($iPublish);
          $this->UpdateEvent($oEvent);
        }
        return true;
      }
    }
    if (isset($aParams['unique_user']) and $aParams['unique_user']) {
      /**
       * Проверяем на уникальность для конкретного пользователя
       */
      if ($oEvent = $this->GetEventByTarget($sEventType, $iTargetId, $iUserId)) {
        /**
         * Событие уже было
         */
        if ($oEvent->getPublish() != $iPublish) {
          $oEvent->setPublish($iPublish);
          $this->UpdateEvent($oEvent);
        }
        return true;
      }
    }

    if ($iPublish) {
      /**
       * Создаем новое событие
       */
      $oEvent = Engine::GetEntity('Stream_Event');
      $oEvent->setEventType($sEventType);
      $oEvent->setUserId($iUserId);
      $oEvent->setTargetId($iTargetId);
      $oEvent->setDateAdded(date("Y-m-d H:i:s"));
      $oEvent->setPublish($iPublish);
      $this->AddEvent($oEvent);
    }
    return true;
  }

}

?>
