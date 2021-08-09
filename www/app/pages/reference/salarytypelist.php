<?php

namespace App\Pages\Reference;

use App\Entity\SalType;
use Zippy\Html\DataList\DataView;
use Zippy\Html\Form\Button;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Form\CheckBox;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

//начисления  удержания
class SalaryTypeList extends \App\Pages\Base
{

    private $_st;

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowRef('SalaryTypeList')) {
            return;
        }

        $this->add(new Panel('tablepan')) ;
        $this->tablepan->add(new DataView('stlist', new \ZCL\DB\EntityDataSource('\App\Entity\SalType', '', 'salcode'), $this, 'listOnRow'))->Reload();
        $this->tablepan->add(new ClickLink('addnew'))->onClick($this, 'addOnClick');
      
        $this->add(new Form('editform'))->setVisible(false);
        $this->editform->add(new TextInput('editstname'));
        $this->editform->add(new TextInput('editshortname'));
        $this->editform->add(new TextInput('editcode'));
        $this->editform->add(new CheckBox('editdisabled'));
        $this->editform->add(new SubmitButton('save'))->onClick($this, 'saveOnClick');
        $this->editform->add(new Button('cancel'))->onClick($this, 'cancelOnClick');
        $this->editform->add(new Button('delete'))->onClick($this, 'deleteOnClick');
    }

    public function listOnRow($row) {
        $item = $row->getDataItem();

        $row->add(new Label('stname', $item->salname));
        $row->add(new Label('shortname', $item->salshortname));
        $row->add(new Label('code', $item->salcode));
        $row->add(new ClickLink('edit'))->onClick($this, 'editOnClick');
     }

    public function deleteOnClick($sender) {
        if (false == \App\ACL::checkDelRef('SalaryTypeList')) {
            return;
        }
        $sa = $sender->owner->getDataItem();

        $del = SalType::delete($sa->st_id);
        if (strlen($del) > 0) {
            $this->setError($del);
            return;
        }
        $this->tablepan->list->Reload();
    }

    public function editOnClick($sender) {
        $this->_st = $sender->owner->getDataItem();
        $this->tablepan->setVisible(false);
        $this->editform->setVisible(true);
        $this->editform->editstname->setText($this->_st->salname);
        $this->editform->editshortname->setText($this->_st->salshortname);
        $this->editform->editdisabled->setChecked($this->_st->disabled);
        $this->editform->editcode->setText($this->_st->salcode);
    }

    public function addOnClick($sender) {
        $this->tablepan->setVisible(false);
        $this->editform->setVisible(true);
        // Очищаем  форму
        $this->editform->clean();

        $this->_st = new SalType();
    }

    public function saveOnClick($sender) {
        if (false == \App\ACL::checkEditRef('SalaryTypeList')) {
            return;
        }


       $this->_st->salname  = $this->editform->editstname->getText();
       $this->_st->salshortname = $this->editform->editshortname->getText();
       $this->_st->salcode = $this->editform->editcode->getText();
       $this->_st->disabled = $this->editform->editdisabled->ischecked() ? 1 : 0;
      
  
       $code = intval($this->_st->salcode) ;
       if($code < 100 || $code > 999) {
           $this->setError('invalidcode') ;
           return;
       }
        $c =  SalType::getFirst("salcode={$code} and st_id<>". $this->_st->st_id) ;    
        if($c != null) {
           $this->setError('codeexists') ;
           return;
          
        }
        
        $this->_st->save();
        $this->editform->setVisible(false);
        $this->tablepan->setVisible(true);
        $this->tablepan->stlist->Reload();
    }

    public function cancelOnClick($sender) {
        $this->tablepan->setVisible(true);
        $this->editform->setVisible(false);
    }

}
