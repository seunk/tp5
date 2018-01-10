<?php
namespace app\common\widget;

use think\Controller;

class MessageWidget extends Controller{

    public function render($data=null)
    {
        if(!is_login()){
            return false;
        }
        return $this->fetch(T('application://common@widget/message'));
    }

} 