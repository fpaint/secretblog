<?php

class PluginSecretblog_ActionTopic extends PluginSecretblog_Inherit_ActionTopic {
  
  protected function blogIsClosed($oBlog) {
    return in_array($oBlog->getType(), array('close', 'secret'));
  }
  
  
	/**
	 * Обработка добавления топика
	 *
	 */
	protected function SubmitAdd__() {
		/**
		 * Проверяем отправлена ли форма с данными(хотяб одна кнопка)
		 */
		if (!isPost('submit_topic_publish') and !isPost('submit_topic_save')) {
			return false;
		}
		$oTopic=Engine::GetEntity('Topic');
		$oTopic->_setValidateScenario('topic');
		/**
		 * Заполняем поля для валидации
		 */
		$oTopic->setBlogId(getRequestStr('blog_id'));
		$oTopic->setTitle(strip_tags(getRequestStr('topic_title')));
		$oTopic->setTextSource(getRequestStr('topic_text'));
		$oTopic->setTags(getRequestStr('topic_tags'));
		$oTopic->setUserId($this->oUserCurrent->getId());
		$oTopic->setType('topic');
		$oTopic->setDateAdd(date("Y-m-d H:i:s"));
		$oTopic->setUserIp(func_getIp());
		/**
		 * Проверка корректности полей формы
		 */
		if (!$this->checkTopicFields($oTopic)) {
			return false;
		}
		/**
		 * Определяем в какой блог делаем запись
		 */
		$iBlogId=$oTopic->getBlogId();
		if ($iBlogId==0) {
			$oBlog=$this->Blog_GetPersonalBlogByUserId($this->oUserCurrent->getId());
		} else {
			$oBlog=$this->Blog_GetBlogById($iBlogId);
		}
		/**
		 * Если блог не определен выдаем предупреждение
		 */
		if (!$oBlog) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_create_blog_error_unknown'),$this->Lang_Get('error'));
			return false;
		}
		/**
		 * Проверяем права на постинг в блог
		 */
		if (!$this->ACL_IsAllowBlog($oBlog,$this->oUserCurrent)) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_create_blog_error_noallow'),$this->Lang_Get('error'));
			return false;
		}
		/**
		 * Проверяем разрешено ли постить топик по времени
		 */
		if (isPost('submit_topic_publish') and !$this->ACL_CanPostTopicTime($this->oUserCurrent)) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_time_limit'),$this->Lang_Get('error'));
			return;
		}
		/**
		 * Теперь можно смело добавлять топик к блогу
		 */
		$oTopic->setBlogId($oBlog->getId());
		/**
		 * Получаемый и устанавливаем разрезанный текст по тегу <cut>
		 */
		list($sTextShort,$sTextNew,$sTextCut) = $this->Text_Cut($oTopic->getTextSource());

		$oTopic->setCutText($sTextCut);
		$oTopic->setText($this->Text_Parser($sTextNew));
		$oTopic->setTextShort($this->Text_Parser($sTextShort));
		/**
		 * Публикуем или сохраняем
		 */
		if (isset($_REQUEST['submit_topic_publish'])) {
			$oTopic->setPublish(1);
			$oTopic->setPublishDraft(1);
		} else {
			$oTopic->setPublish(0);
			$oTopic->setPublishDraft(0);
		}
		/**
		 * Принудительный вывод на главную
		 */
		$oTopic->setPublishIndex(0);
		if ($this->ACL_IsAllowPublishIndex($this->oUserCurrent))	{
			if (getRequest('topic_publish_index')) {
				$oTopic->setPublishIndex(1);
			}
		}
		/**
		 * Запрет на комментарии к топику
		 */
		$oTopic->setForbidComment(0);
		if (getRequest('topic_forbid_comment')) {
			$oTopic->setForbidComment(1);
		}
		/**
		 * Запускаем выполнение хуков
		 */
		$this->Hook_Run('topic_add_before', array('oTopic'=>$oTopic,'oBlog'=>$oBlog));
		/**
		 * Добавляем топик
		 */
		if ($this->Topic_AddTopic($oTopic)) {
			$this->Hook_Run('topic_add_after', array('oTopic'=>$oTopic,'oBlog'=>$oBlog));
			/**
			 * Получаем топик, чтоб подцепить связанные данные
			 */
			$oTopic=$this->Topic_GetTopicById($oTopic->getId());
			/**
			 * Обновляем количество топиков в блоге
			 */
			$this->Blog_RecalculateCountTopicByBlogId($oTopic->getBlogId());
			/**
			 * Добавляем автора топика в подписчики на новые комментарии к этому топику
			 */
			$this->Subscribe_AddSubscribeSimple('topic_new_comment',$oTopic->getId(),$this->oUserCurrent->getMail());
			/**
			 * Делаем рассылку спама всем, кто состоит в этом блоге
			 */
			if ($oTopic->getPublish()==1 and $oBlog->getType()!='personal') {
				$this->Topic_SendNotifyTopicNew($oBlog,$oTopic,$this->oUserCurrent);
			}
			/**
			 * Добавляем событие в ленту
			 */
			$this->Stream_write($oTopic->getUserId(), 'add_topic', $oTopic->getId(),$oTopic->getPublish() && !$this->blogIsClosed($oBlog));
			Router::Location($oTopic->getUrl());
		} else {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'));
			return Router::Action('error');
		}
	}
	/**
	 * Обработка редактирования топика
	 *
	 * @param ModuleTopic_EntityTopic $oTopic
	 * @return mixed
	 */
	protected function SubmitEdit__($oTopic) {
		$oTopic->_setValidateScenario('topic');
		/**
		 * Сохраняем старое значение идентификатора блога
		 */
		$sBlogIdOld = $oTopic->getBlogId();
		/**
		 * Заполняем поля для валидации
		 */
		$oTopic->setBlogId(getRequestStr('blog_id'));
		$oTopic->setTitle(strip_tags(getRequestStr('topic_title')));
		$oTopic->setTextSource(getRequestStr('topic_text'));
		$oTopic->setTags(getRequestStr('topic_tags'));
		$oTopic->setUserIp(func_getIp());
		/**
		 * Проверка корректности полей формы
		 */
		if (!$this->checkTopicFields($oTopic)) {
			return false;
		}
		/**
		 * Определяем в какой блог делаем запись
		 */
		$iBlogId=$oTopic->getBlogId();
		if ($iBlogId==0) {
			$oBlog=$this->Blog_GetPersonalBlogByUserId($oTopic->getUserId());
		} else {
			$oBlog=$this->Blog_GetBlogById($iBlogId);
		}
		/**
		 * Если блог не определен выдаем предупреждение
		 */
		if (!$oBlog) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_create_blog_error_unknown'),$this->Lang_Get('error'));
			return false;
		}
		/**
		 * Проверяем права на постинг в блог
		 */
		if (!$this->ACL_IsAllowBlog($oBlog,$this->oUserCurrent)) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_create_blog_error_noallow'),$this->Lang_Get('error'));
			return false;
		}
		/**
		 * Проверяем разрешено ли постить топик по времени
		 */
		if (isPost('submit_topic_publish') and !$oTopic->getPublishDraft() and !$this->ACL_CanPostTopicTime($this->oUserCurrent)) {
			$this->Message_AddErrorSingle($this->Lang_Get('topic_time_limit'),$this->Lang_Get('error'));
			return;
		}
		$oTopic->setBlogId($oBlog->getId());
		/**
		 * Получаемый и устанавливаем разрезанный текст по тегу <cut>
		 */
		list($sTextShort,$sTextNew,$sTextCut) = $this->Text_Cut($oTopic->getTextSource());

		$oTopic->setCutText($sTextCut);
		$oTopic->setText($this->Text_Parser($sTextNew));
		$oTopic->setTextShort($this->Text_Parser($sTextShort));
		/**
		 * Публикуем или сохраняем в черновиках
		 */
		$bSendNotify=false;
		if (isset($_REQUEST['submit_topic_publish'])) {
			$oTopic->setPublish(1);
			if ($oTopic->getPublishDraft()==0) {
				$oTopic->setPublishDraft(1);
				$oTopic->setDateAdd(date("Y-m-d H:i:s"));
				$bSendNotify=true;
			}
		} else {
			$oTopic->setPublish(0);
		}
		/**
		 * Принудительный вывод на главную
		 */
		if ($this->ACL_IsAllowPublishIndex($this->oUserCurrent))	{
			if (getRequest('topic_publish_index')) {
				$oTopic->setPublishIndex(1);
			} else {
				$oTopic->setPublishIndex(0);
			}
		}
		/**
		 * Запрет на комментарии к топику
		 */
		$oTopic->setForbidComment(0);
		if (getRequest('topic_forbid_comment')) {
			$oTopic->setForbidComment(1);
		}
		$this->Hook_Run('topic_edit_before', array('oTopic'=>$oTopic,'oBlog'=>$oBlog));
		/**
		 * Сохраняем топик
		 */
		if ($this->Topic_UpdateTopic($oTopic)) {
			$this->Hook_Run('topic_edit_after', array('oTopic'=>$oTopic,'oBlog'=>$oBlog,'bSendNotify'=>&$bSendNotify));
			/**
			 * Обновляем данные в комментариях, если топик был перенесен в новый блог
			 */
			if($sBlogIdOld!=$oTopic->getBlogId()) {
				$this->Comment_UpdateTargetParentByTargetId($oTopic->getBlogId(), 'topic', $oTopic->getId());
				$this->Comment_UpdateTargetParentByTargetIdOnline($oTopic->getBlogId(), 'topic', $oTopic->getId());
			}
			/**
			 * Обновляем количество топиков в блоге
			 */
			if ($sBlogIdOld!=$oTopic->getBlogId()) {
				$this->Blog_RecalculateCountTopicByBlogId($sBlogIdOld);
			}
			$this->Blog_RecalculateCountTopicByBlogId($oTopic->getBlogId());
			/**
			 * Добавляем событие в ленту
			 */
			$this->Stream_write($oTopic->getUserId(), 'add_topic', $oTopic->getId(),$oTopic->getPublish() && !$this->blogIsClosed($oBlog));
			/**
			 * Рассылаем о новом топике подписчикам блога
			 */
			if ($bSendNotify)	 {
				$this->Topic_SendNotifyTopicNew($oBlog,$oTopic,$oTopic->getUser());
			}
			if (!$oTopic->getPublish() and !$this->oUserCurrent->isAdministrator() and $this->oUserCurrent->getId()!=$oTopic->getUserId()) {
				Router::Location($oBlog->getUrlFull());
			}
			Router::Location($oTopic->getUrl());
		} else {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'));
			return Router::Action('error');
		}
	}
  
}

?>