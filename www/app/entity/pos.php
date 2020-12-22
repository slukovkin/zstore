<?php

namespace App\Entity;

/**
 * Клас-сущность  терминал
 *
 * @table=poslist
 * @keyfield=pos_id
 */
class Pos extends \ZCL\DB\Entity
{

    protected function init() {
        $this->pos_id = 0;
        $this->fiscalnumber = 0;
    }

    protected function beforeSave() {
        parent::beforeSave();
        //упаковываем  данные в detail
        $this->details = "<details>";

        $this->details .= "<comment><![CDATA[{$this->comment}]]></comment>";
        $this->details .= "<address><![CDATA[{$this->address}]]></address>";

        $this->details .= "<pricetype>{$this->pricetype}</pricetype>";
        $this->details .= "<mf>{$this->mf}</mf>";
        $this->details .= "<store>{$this->store}</store>";
        $this->details .= "<fiscalnumber>{$this->fiscalnumber}</fiscalnumber>";
        $this->details .= "<fiscallocnumber>{$this->fiscallocnumber}</fiscallocnumber>";
        $this->details .= "<fiscdocnumber>{$this->fiscdocnumber}</fiscdocnumber>";
        
        
        $this->details .= "<usefisc>{$this->usefisc}</usefisc>";
        $this->details .= "</details>";

        return true;
    }

    protected function afterLoad() {
        //распаковываем  данные из detail
        $xml = simplexml_load_string($this->details);

        $this->mf = (int)($xml->mf[0]);
        $this->pricetype = (string)($xml->pricetype[0]);
        $this->store = (int)($xml->store[0]);
        $this->comment = (string)($xml->comment[0]);
        $this->address = (string)($xml->address[0]);
        $this->fiscalnumber = (string)($xml->fiscalnumber[0]);
        $this->fiscallocnumber = (int)($xml->fiscallocnumber[0]);
        $this->fiscdocnumber = (int)($xml->fiscdocnumber[0]);
        
        $this->usefisc = (int)($xml->usefisc[0]);
        if(strlen($this->fiscdocnumber)==0)$this->fiscdocnumber=1;   
        parent::afterLoad();
    }

    public static function getConstraint() {
        return \App\ACL::getBranchConstraint();
    }

}
