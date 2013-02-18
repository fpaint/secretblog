<?php

class PluginSecretblog_ActionBlog extends PluginSecretblog_Inherit_ActionBlog {

  /**
   * Проверка полей блога
   *
   * @param ModuleBlog_EntityBlog|null $oBlog
   * @return bool
   */
  protected function checkBlogFields($oBlog = null) {
    /**
     * Проверяем только если была отправлена форма с данными (методом POST)
     */
    if (!isPost('submit_blog_add')) {
      $_REQUEST['blog_limit_rating_topic'] = 0;
      return false;
    }
    $this->Security_ValidateSendForm();

    $bOk = true;
    /**
     * Проверяем есть ли название блога
     */
    if (!func_check(getRequestStr('blog_title'), 'text', 2, 200)) {
      $this->Message_AddError($this->Lang_Get('blog_create_title_error'), $this->Lang_Get('error'));
      $bOk = false;
    } else {
      /**
       * Проверяем есть ли уже блог с таким названием
       */
      if ($oBlogExists = $this->Blog_GetBlogByTitle(getRequestStr('blog_title'))) {
        if (!$oBlog or $oBlog->getId() != $oBlogExists->getId()) {
          $this->Message_AddError($this->Lang_Get('blog_create_title_error_unique'), $this->Lang_Get('error'));
          $bOk = false;
        }
      }
    }

    /**
     * Проверяем есть ли URL блога, с заменой всех пробельных символов на "_"
     */
    if (!$oBlog or $this->oUserCurrent->isAdministrator()) {
      $blogUrl = preg_replace("/\s+/", '_', getRequestStr('blog_url'));
      $_REQUEST['blog_url'] = $blogUrl;
      if (!func_check(getRequestStr('blog_url'), 'login', 2, 50)) {
        $this->Message_AddError($this->Lang_Get('blog_create_url_error'), $this->Lang_Get('error'));
        $bOk = false;
      }
    }
    /**
     * Проверяем на счет плохих УРЛов
     */
    if (in_array(getRequestStr('blog_url'), $this->aBadBlogUrl)) {
      $this->Message_AddError($this->Lang_Get('blog_create_url_error_badword') . ' ' . join(',', $this->aBadBlogUrl), $this->Lang_Get('error'));
      $bOk = false;
    }
    /**
     * Проверяем есть ли уже блог с таким URL
     */
    if ($oBlogExists = $this->Blog_GetBlogByUrl(getRequestStr('blog_url'))) {
      if (!$oBlog or $oBlog->getId() != $oBlogExists->getId()) {
        $this->Message_AddError($this->Lang_Get('blog_create_url_error_unique'), $this->Lang_Get('error'));
        $bOk = false;
      }
    }
    /**
     * Проверяем есть ли описание блога
     */
    if (!func_check(getRequestStr('blog_description'), 'text', 10, 3000)) {
      $this->Message_AddError($this->Lang_Get('blog_create_description_error'), $this->Lang_Get('error'));
      $bOk = false;
    }
    /**
     * Проверяем доступные типы блога для создания
     */
    if (!in_array(getRequestStr('blog_type'), array('open', 'close', 'secret'))) {
      $this->Message_AddError($this->Lang_Get('blog_create_type_error'), $this->Lang_Get('error'));
      $bOk = false;
    }
    /**
     * Преобразуем ограничение по рейтингу в число
     */
    if (!func_check(getRequestStr('blog_limit_rating_topic'), 'float')) {
      $this->Message_AddError($this->Lang_Get('blog_create_rating_error'), $this->Lang_Get('error'));
      $bOk = false;
    }
    /**
     * Выполнение хуков
     */
    $this->Hook_Run('check_blog_fields', array('bOk' => &$bOk));
    return $bOk;
  }
  
	/**
	 * Подключение/отключение к блогу
	 *
	 */
	protected function AjaxBlogJoin() {
		/**
		 * Устанавливаем формат Ajax ответа
		 */
		$this->Viewer_SetResponseAjax('json');
		/**
		 * Пользователь авторизован?
		 */
		if (!$this->oUserCurrent) {
			$this->Message_AddErrorSingle($this->Lang_Get('need_authorization'),$this->Lang_Get('error'));
			return;
		}
		/**
		 * Блог существует?
		 */
		$idBlog=getRequestStr('idBlog',null,'post');
		if (!($oBlog=$this->Blog_GetBlogById($idBlog))) {
			$this->Message_AddErrorSingle($this->Lang_Get('system_error'),$this->Lang_Get('error'));
			return;
		}
		/**
		 * Проверяем тип блога
		 */
		if (!in_array($oBlog->getType(),array('open','close','secret'))) {
			$this->Message_AddErrorSingle($this->Lang_Get('blog_join_error_invite'),$this->Lang_Get('error'));
			return;
		}
		/**
		 * Получаем текущий статус пользователя в блоге
		 */
		$oBlogUser=$this->Blog_GetBlogUserByBlogIdAndUserId($oBlog->getId(),$this->oUserCurrent->getId());
		if (!$oBlogUser || ($oBlogUser->getUserRole()<ModuleBlog::BLOG_USER_ROLE_GUEST && $oBlog->getType()=='close')) {
			if ($oBlog->getOwnerId()!=$this->oUserCurrent->getId()) {
				/**
				 * Присоединяем юзера к блогу
				 */
				$bResult=false;
				if($oBlogUser) {
					$oBlogUser->setUserRole(ModuleBlog::BLOG_USER_ROLE_USER);
					$bResult = $this->Blog_UpdateRelationBlogUser($oBlogUser);
				} elseif(in_array($oBlog->getType(), array('open', 'secret'))) {
					$oBlogUserNew=Engine::GetEntity('Blog_BlogUser');
					$oBlogUserNew->setBlogId($oBlog->getId());
					$oBlogUserNew->setUserId($this->oUserCurrent->getId());
					$oBlogUserNew->setUserRole(ModuleBlog::BLOG_USER_ROLE_USER);
					$bResult = $this->Blog_AddRelationBlogUser($oBlogUserNew);
				}
				if ($bResult) {
					$this->Message_AddNoticeSingle($this->Lang_Get('blog_join_ok'),$this->Lang_Get('attention'));
					$this->Viewer_AssignAjax('bState',true);
					/**
					 * Увеличиваем число читателей блога
					 */
					$oBlog->setCountUser($oBlog->getCountUser()+1);
					$this->Blog_UpdateBlog($oBlog);
					$this->Viewer_AssignAjax('iCountUser',$oBlog->getCountUser());
					/**
					 * Добавляем событие в ленту
					 */
					$this->Stream_write($this->oUserCurrent->getId(), 'join_blog', $oBlog->getId());
					/**
					 * Добавляем подписку на этот блог в ленту пользователя
					 */
					$this->Userfeed_subscribeUser($this->oUserCurrent->getId(), ModuleUserfeed::SUBSCRIBE_TYPE_BLOG, $oBlog->getId());
				} else {
					$sMsg=($oBlog->getType()=='close')
						? $this->Lang_Get('blog_join_error_invite')
						: $this->Lang_Get('system_error');
					$this->Message_AddErrorSingle($sMsg,$this->Lang_Get('error'));
					return;
				}
			} else {
				$this->Message_AddErrorSingle($this->Lang_Get('blog_join_error_self'),$this->Lang_Get('attention'));
				return;
			}
		}
		if ($oBlogUser && $oBlogUser->getUserRole()>ModuleBlog::BLOG_USER_ROLE_GUEST) {
			/**
			 * Покидаем блог
			 */
			if ($this->Blog_DeleteRelationBlogUser($oBlogUser)) {
				$this->Message_AddNoticeSingle($this->Lang_Get('blog_leave_ok'),$this->Lang_Get('attention'));
				$this->Viewer_AssignAjax('bState',false);
				/**
				 * Уменьшаем число читателей блога
				 */
				$oBlog->setCountUser($oBlog->getCountUser()-1);
				$this->Blog_UpdateBlog($oBlog);
				$this->Viewer_AssignAjax('iCountUser',$oBlog->getCountUser());
				/**
				 * Удаляем подписку на этот блог в ленте пользователя
				 */
				$this->Userfeed_unsubscribeUser($this->oUserCurrent->getId(), ModuleUserfeed::SUBSCRIBE_TYPE_BLOG, $oBlog->getId());
			} else {
				$this->Message_AddErrorSingle($this->Lang_Get('system_error'),$this->Lang_Get('error'));
				return;
			}
		}
	}  

}

?>
