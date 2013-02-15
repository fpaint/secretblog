<?php

if (!class_exists('Plugin')) {
    die('Hacking attemp!');
}

class PluginSecretblog extends Plugin {

    protected $aInherits = array( 
      'action' => array(
        'ActionBlog'
      ),
      'module' => array(
        'ModuleTopic',
        'ModuleBlog',
        'ModuleStream'
      ),
      'mapper' => array(
        'ModuleTopic_MapperTopic',
        'ModuleBlog_MapperBlog'
      )
    );
    protected $aDelegates = array(
        'template' => array(
          'actions/ActionBlog/add.tpl'
        ),
        'action' => array(
          'ActionBlogs'
        )
    );


    // Активация плагина
    public function Activate() {
        $this->Cache_Clean();
        $resutls = $this->ExportSQL(dirname(__FILE__) . '/secret_patch.sql');
        return $resutls['result'];
    }

    // Деактивация плагина
    public function Deactivate(){       
      $this->Cache_Clean();
    	return true;
    }
    // Инициализация плагина
    public function Init() {
    
    }
}
?>
