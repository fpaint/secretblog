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

}

?>
