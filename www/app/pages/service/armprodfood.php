<?php

namespace App\Pages\Service;

use App\Application as App;
use App\Entity\Doc\Document;
use App\Helper as H;
use App\System;
use Zippy\Html\DataList\DataView;
use Zippy\Html\DataList\ArrayDataSource;
 
use Zippy\Html\Form\Date;
use Zippy\Html\Form\DropDownChoice;
use Zippy\Html\Form\Form;
use Zippy\Html\Form\SubmitButton;
use Zippy\Html\Form\TextInput;
use Zippy\Html\Label;
use Zippy\Html\Link\ClickLink;
use Zippy\Html\Panel;

/**
 * АРМ кухни (бара)
 */
class ArmProdFood extends \App\Pages\Base
{

 
    public  $_itemlist    = array();

    public function __construct() {
        parent::__construct();
        if (false == \App\ACL::checkShowSer('ArmProdFood')) {
            return;
        }

        $this->add(new DataView('itemlist', new ArrayDataSource($this, '_itemlist'), $this, 'onRow'));
        $this->add(new  ClickLink("btnupdate",$this,'update') );
        
        $this->update();
       
    }
    
    
    public function onRow($row){
       $item = $row->getDataItem();
       $row->add(new  Label("docnumber",$item->ordern) );
       $row->add(new  Label("docnotes",$item->docnotes) );
       $row->add(new  Label("name",$item->itemname) );
       $row->add(new  Label("qty",$item->quantity) );
       $notes ="";
       if($item->myself == 1)   $notes =H::l("myself");
       if($item->del == true)   $notes =H::l("delivery"); 
       $row->add(new  Label("notes",$notes) );
       $row->add(new  ClickLink("ready",$this,'onReady') );
         
    }   
    
    public function update($sender=null){
        $this->_itemlist  = array(); 
        $where ="meta_name='OrderFood' and state in (7) " ;
        
        $docs = Document::find($where,"  document_id");
        
        foreach($docs  as $doc)  {
            $items = $doc->unpackDetails('detaildata');
            foreach($items as $item) {
               if($item->foodstate==1) continue;
               
               $item->ordern = $doc->document_number ;
               $item->docnotes = $doc->notes ;
               $item->document_id = $doc->document_id ;
               $item->del = $doc->headerdata['delivery'] >0 ;
                
               $this->_itemlist[]=$item; 
            }
        }
        
        $this->itemlist->Reload();
    }
    
    
    public function onReady($sender){
        $item = $sender->getOwner()->getDataItem();
        $doc = Document::load($item->document_id) ;
        $items = $doc->unpackDetails('detaildata');
        $items[$item->item_id]->foodstate=1;
        $doc->packDetails('detaildata',$items);
        $doc->save();
        $hasinproces = false;
        foreach($items as $it) {
            if($it->foodstate!==1) $hasinproces=true;
        }
        if($hasinproces == false) { 
           $doc->updateStatus(Document::STATE_FINISHED) ;  
           if($doc->payed>0) {
               $doc->updateStatus(Document::STATE_CLOSED) ;   
           }
           
               
        }
        $this->update(null) ;
    }
   
    public function getMessages($args,$post){
       return    json_encode(array(1,2,3), JSON_UNESCAPED_UNICODE);
    }
    
    
}