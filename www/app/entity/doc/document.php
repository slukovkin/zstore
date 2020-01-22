<?php

namespace App\Entity\Doc;

use \App\System;
use \App\Helper;

/**
 * Класс-сущность документ
 *
 */
class Document extends \ZCL\DB\Entity {

    // состояния  документа
    const STATE_NEW = 1;     //Новый
    const STATE_EDITED = 2;  //Отредактирован
    const STATE_CANCELED = 3;      //Отменен
    const STATE_READYTOEXE = 4; // готов к выполнению
    
    
    const STATE_EXECUTED = 5;      // Проведен 
    
    const STATE_DELETED = 6;       //  Удален
    const STATE_INPROCESS = 7; // в  работе
    const STATE_WA = 8; // ждет подтверждения
    const STATE_CLOSED = 9; // Закрыт , доставлен, выполнен
    const STATE_INSHIPMENT = 11; // Отгружен
    const STATE_DELIVERED = 14; // доставлен
    const STATE_REFUSED = 15; // отклонен
    const STATE_SHIFTED = 16; // отложен
    const STATE_FAIL = 17; // Аннулирован
    const STATE_FINISHED = 18; // Закончен
    
    const STATE_APPROVED = 19;      //  Утвержден
    // типы  экспорта
    const EX_WORD = 1; //  Word
    const EX_EXCEL = 2;    //  Excel
    const EX_PDF = 3;    //  PDF
    const EX_POS = 4;    //  POS терминал

    // const EX_XML_GNAU = 4;

    /**
     * Ассоциативный массив   с атрибутами заголовка  документа
     *
     * @var mixed
     */
    public $headerdata = array();

    /**
     * Массив  ассоциативных массивов (строк) содержащих  строки  детальной части (таблицы) документа
     *
     * @var mixed
     */
    public $detaildata = array();

    /**
     * документы должны создаватся методом create
     * 
     * @param mixed $row
     */
    protected function __construct($row = null) {
        parent::__construct($row);
    }

    /**
     * начальная инициализация. Вызывается автоматически  в  конструкторе  Entity
     * 
     */
    protected function init() {
        $this->document_id = 0;
        $this->state = 0;
        $this->customer_id = 0;
        $this->branch_id = 0;
        $this->parent_id = 0;

        $this->document_number = '';
        $this->notes = '';

        $this->document_date = time();
        $this->user_id = \App\System::getUser()->user_id;


        $this->headerdata = array();
        $this->detaildata = array();
    }

    /**
     * возвращает метаданные  чтобы  работало в  дочерних классах
     * 
     */
    protected static function getMetadata() {
        return array('table' => 'documents', 'view' => 'documents_view', 'keyfield' => 'document_id');
    }

    protected function afterLoad() {
        $this->document_date = strtotime($this->document_date);
        $this->unpackData();
    }

    protected function beforeSave() {
        $this->document_number = trim($this->document_number);
        $this->packData();
        $doc = Document::getFirst("   document_number = '{$this->document_number}' ");
        if ($doc instanceof Document) {
            if ($this->document_id != $doc->document_id) {

                throw new \Exception('Не уникальный номер документа ');
                return false;
            }
        }
    }

    /**
     * Упаковка  данных  в  XML
     *
     */
    private function packData() {


        $this->content = "<doc><header>";

        foreach ($this->headerdata as $key => $value) {
            if ($key > 0)
                continue;

            if (strpos($value, '[CDATA[') !== false) {
                \App\System::getWarnMsg('CDATA в  поле  обьекта');
                \App\Helper::log(' CDATA в  поле  обьекта');
                continue;
            }

            if (is_numeric($value) || strlen($value) == 0) {
                $value = $value;
            } else {
                $value = "<![CDATA[" . $value . "]]>";
            }
            $this->content .= "<{$key}>{$value}</{$key}>";
        }
        $this->content .= "</header><detail>";
        foreach ($this->detaildata as $row) {
            $this->content .= "<row>";
            foreach ($row as $key => $value) {
                if ($key > 0)
                    continue;

                if (strpos($value, '[CDATA[') !== false) {
                    \App\System::getWarnMsg('CDATA в  поле  обьекта');
                    \App\Helper::log(' CDATA в  поле  обьекта');
                    continue;
                }


                if (is_numeric($value) || strlen($value) == 0) {
                    $value = $value;
                } else {
                    $value = "<![CDATA[" . $value . "]]>";
                }

                $this->content .= "<{$key}>{$value}</{$key}>";
            }

            $this->content .= "</row>";
        }
        $this->content .= "</detail></doc> ";
    }

    /**
     * распаковка из  XML
     *
     */
    private function unpackData() {

        $this->headerdata = array();
        if (strlen($this->content) == 0) {
            return;
        }

        try {
            $xml = new \SimpleXMLElement($this->content);
        } catch (\Exception $ee) {
            global $logger;
            $logger->error("Документ " . $this->document_number . " " . $ee->getMessage());
            return;
        }
        foreach ($xml->header->children() as $child) {
            $this->headerdata[(string) $child->getName()] = (string) $child;
        }
        $this->detaildata = array();
        foreach ($xml->detail->children() as $row) {
            $_row = array();
            foreach ($row->children() as $item) {
                $_row[(string) $item->getName()] = (string) $item;
            }
            $this->detaildata[] = $_row;
        }
    }

    /**
     * Генерация HTML  для  печатной формы
     *
     */
    public function generateReport() {
        return "";
    }

    /**
     * Генерация  печати для POS  терминала
     *
     */
    public function generatePosReport() {
        return "";
    }

    /**
     * Выполнение документа - обновление склада, бухгалтерские проводки и  т.д.
     *
     */
    public function Execute() {
        
    }

    /**
     * Отмена  документа
     *
     */
    protected function Cancel() {
        $conn = \ZDB\DB::getConnect();
        $conn->StartTrans();
        try {
            // если  метод не переопределен  в  наследнике удаляем  документ  со  всех  движений
            $conn->Execute("delete from entrylist where document_id =" . $this->document_id);
            //удаляем освободившиеся стоки
            $conn->Execute("delete from store_stock where stock_id not in (select coalesce(stock_id,0) from entrylist) ");


            //отменяем оплаты  но  в  документе  оставляем
            $sql = "select coalesce( sum(amount),0) from paylist where document_id=" . $this->document_id;
            $payed = $conn->GetOne($sql);
            if ($payed != 0) {
                \App\Entity\Pay::addPayment($this->document_id, 0 - $payed, $this->headerdata['payment'], \App\Entity\Pay::PAY_CANCEL, 'Отмена  документа');
            }
            // $this->payed=0;
            // $this->save();
            //$conn->Execute("update documents set payed=0 where   document_id =" . $this->document_id);
            // возвращаем бонусы
            if ($this->headerdata['paydisc'] > 0) {
                $customer = \App\Entity\Customer::load($this->customer_id);
                if ($customer->discount > 0) {
                    return; //процент
                } else {
                    $customer->bonus = $customer->bonus + $this->headerdata['paydisc'];
                    $customer->save();
                }
            }



            $conn->CompleteTrans();
        } catch (\Exception $ee) {
            global $logger;
            $conn->RollbackTrans();
            \App\System::setErrorMsg($ee->getMessage());

            $logger->error($ee->getMessage() . " Документ " . $this->_doc->meta_desc);

            return false;
        }
        return true;
    }

    /**
     * создает  экземпляр  класса  документа   в   соответсии  с  именем  типа
     *
     * @param mixed $classname
     */
    public static function create($classname) {
        $arr = explode("\\", $classname);
        $classname = $arr[count($arr) - 1];
        $conn = \ZDB\DB::getConnect();
        $sql = "select meta_id from  metadata where meta_type=1 and meta_name='{$classname}'";
        $meta = $conn->GetRow($sql);
        $fullclassname = '\App\Entity\Doc\\' . $classname;

        $doc = new $fullclassname();
        $doc->meta_id = $meta['meta_id'];

        $doc->branch_id = \App\Acl::checkCurrentBranch();
        return $doc;
    }

    /**
     * Приведение  типа и клонирование  документа
     */
    public function cast() {

        if (strlen($this->meta_name) == 0) {
            $metarow = Helper::getMetaType($this->meta_id);
            $this->meta_name = $metarow['meta_name'];
        }
        $class = "\\App\\Entity\\Doc\\" . $this->meta_name;
        $doc = new $class($this->getData());
        $doc->unpackData();
        return $doc;
    }

    protected function afterSave($update) {

        //  if ($update == false) {   //новый  документ
        //    $this->updateStatus(self::STATE_NEW);
        // }
        // else {
        //    if ($this->state == self::STATE_NEW)
        //    $this->updateStatus(self::STATE_EDITED);
        //  }
    }

    /**
     * Обновляет состояние  документа
     *
     * @param mixed $state
     */
    public function updateStatus($state) {


        if ($this->document_id == 0)
            return false;

        if ($state == self::STATE_CANCELED) {
            $this->Cancel();
        }
        if ($state == self::STATE_EXECUTED) {
            if (false === $this->Execute()) {
                $this->Cancel();
                return;
            }
        }

        $this->state = $state;
        $this->insertLog($state);

        $this->save();


        return true;
    }

    /**
     * Возвращает название  статуса  документа
     *
     * @param mixed $state
     * @return mixed
     */
    public static function getStateName($state) {

        switch ($state) {
            case Document::STATE_NEW:
                return "Новый";
            case Document::STATE_EDITED:
                return "Отредактирован";
            case Document::STATE_CANCELED:
                return "Отменен";
            case Document::STATE_EXECUTED:
                return "Проведен";
            case Document::STATE_CLOSED:
                return "Закрыт";
            case Document::STATE_APPROVED:
                return "Утвержден";
            case Document::STATE_DELETED:
                return "Удален";

            case Document::STATE_WA:
                return "Ожидает утверждения";
            case Document::STATE_INSHIPMENT:
                return "В доставке";
            case Document::STATE_FINISHED:
                return "Выполнен";
            case Document::STATE_DELIVERED:
                return "Доставлен";
            case Document::STATE_REFUSED:
                return "Отклонен";
            case Document::STATE_SHIFTED:
                return "Отложен";
            case Document::STATE_FAIL:
                return "Аннулирован";
            case Document::STATE_INPROCESS:
                return "Выполняется";
           case Document::STATE_READYTOEXE:
                return "Готов к выполнению";
            default:
                return "Неизвестный статус";
        }
    }

    /**
     * Возвращает  следующий  номер  при  автонумерации
     *  
     * @return mixed
     */
    public function nextNumber() {


        $class = explode("\\", get_called_class());
        $metaname = $class[count($class) - 1];
        $doc = Document::getFirst("meta_name='" . $metaname . "'", "document_id desc");
        if ($doc == null) {
            $prevnumber = $this->getNumberTemplate();
        } else {
            $prevnumber = $doc->document_number;
        }



        if (strlen($prevnumber) == 0)
            return '';
        $number = preg_replace('/[^0-9]/', '', $prevnumber);
        if (strlen($number) == 0)
            $number = 0;

        $letter = preg_replace('/[0-9]/', '', $prevnumber);

        return $letter . sprintf("%05d", ++$number);
    }

    /**
     * Возвращает  список  типов экспорта
     * Перегружается  дочерними  для  добавление  специфических  типов
     *
     */
    public function supportedExport() {
        return array(self::EX_EXCEL, self::EX_PDF);
    }

    /**
     * Поиск  документа
     *
     * @param mixed $type имя или id типа
     * @param mixed $from начало  периода  или  null
     * @param mixed $to конец  периода  или  null
     * @param mixed $header значения заголовка
     */
    public static function search($type, $from, $to, $header = array()) {
        $conn = $conn = \ZDB\DB::getConnect();
        ;
        $where = "state= " . Document::STATE_EXECUTED;

        if (strlen($type) > 0) {
            if ($type > 0) {
                $where = $where . " and  meta_id ={$type}";
            } else {
                $where = $where . " and  meta_name='{$type}'";
            }
        }

        if ($from > 0)
            $where = $where . " and  document_date >= " . $conn->DBDate($from);
        if ($to > 0)
            $where = $where . " and  document_date <= " . $conn->DBDate($to);
        foreach ($header as $key => $value) {
            $where = $where . " and  content like '%<{$key}>{$value}</{$key}>%'";
        }

        return Document::find($where);
    }

    /**
     * @see \ZDB\Entity
     * 
     */
    protected function afterDelete() {
        global $logger;

        $conn = \ZDB\DB::getConnect();

        $hasExecuted = $conn->GetOne("select count(*)  from docstatelog where docstate = " . Document::STATE_EXECUTED . " and  document_id=" . $this->document_id);
        $hasPayment = $conn->GetOne("select count(*)  from paylist where   document_id=" . $this->document_id);

        $conn->Execute("delete from docstatelog where document_id=" . $this->document_id);
        $conn->Execute("delete from paylist where document_id=" . $this->document_id);
        $conn->Execute("delete from messages where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from files where item_type=" . \App\Entity\Message::TYPE_DOC . " and item_id=" . $this->document_id);
        $conn->Execute("delete from filesdata where   file_id not in (select file_id from files)");


        //   if(System::getUser()->userlogin =='admin') return;
        if ($hasExecuted || $hasPayment) {
            $admin = \App\Entity\User::getByLogin('admin');

            $n = new \App\Entity\Notify();
            $n->user_id = $admin->user_id;
            $n->message = "Удален документ  <br><br>";
            $n->message .= "Документ {$this->document_number} удален пользователем  " . System::getUser()->username;


            $n->save();
        }
    }

    /**
     *
     *  запись состояния в  лог документа
     * @param mixed $state
     */
    public function insertLog($state) {
        $conn = \ZDB\DB::getConnect();
        $host = $conn->qstr($_SERVER["REMOTE_ADDR"]);
        $user = \App\System::getUser();

        $sql = "insert into docstatelog (document_id,user_id,createdon,docstate,hostname) values({$this->document_id},{$user->user_id},now(),{$state},{$host})";
        $conn->Execute($sql);
    }

    /**
     * список записей   в  логе   состояний
     *
     */
    public function getLogList() {
        $conn = \ZDB\DB::getConnect();
        $rc = $conn->Execute("select * from docstatelog_view where document_id={$this->document_id} order  by  log_id");
        $states = array();
        foreach ($rc as $row) {
            $row['createdon'] = strtotime($row['createdon']);
            $states[] = new \App\DataItem($row);
        }

        return $states;
    }

    /**
     *  проверка  был ли документ в  таких состояниях
     * 
     * @param mixed $states
     */
    public function checkStates(array $states) {
        if (count($states) == 0)
            return false;
        $conn = \ZDB\DB::getConnect();
        $states = implode(',', $states);

        $cnt = $conn->getOne("select count(*) from docstatelog where docstate in({$states}) and document_id={$this->document_id}");
        return $cnt > 0;
    }

    /**
     * возвращает шаблон номераЮ перегружается дочерними классам
     * типа ПР-000000.  Буквенный код должен  быть уникальным для типа документа
     */
    protected function getNumberTemplate() {
        return '';
    }

    public static function getConstraint() {
        $c = \App\ACL::getBranchConstraint();
        $user = System::getUser();
        if ($user->acltype == 2) {
            if (strlen($c) == 0)
                $c = "1=1 ";
            if ($user->onlymy == 1) {

                $c .= " and user_id  = " . $user->user_id;
            }

            $c .= " and meta_id in({$user->aclview}) ";
        }

        return $c;
    }

    /**
     * возвращает  сумму  оптлат
     * 
     */
    public function getPayAmount() {
        $conn = \ZDB\DB::getConnect();

        return $conn->GetOne("select coalesce(sum(amount),0) from paylist where   document_id = {$this->document_id}  ");
    }

    /**
     * put your comment there...
     * 
     */
    public function hasEntry() {
        $conn = \ZDB\DB::getConnect();

        return $conn->GetOne("select coalesce(sum(amount),0) from paylist where   document_id = {$this->document_id}  ");
    }

    /**
     * список  дочерних
     * 
     * @param mixed $type    мета  тип
     * @param mixed $executed  в  состоянии  выполнен и т.д.
     */
    public function getChildren($type = "", $executed = false) {
        $where = "parent_id=" . $this->document_id;
        if (strlen($type) > 0)
            $where .= " and meta_name='{$type}'";
        if ($executed)
            $where .= " and state not in(1,2,3,0) ";
        return Document::find($where);
    }

    public function addChild($id) {
        if ($id > 0) {
            $conn = \ZDB\DB::getConnect();
            $conn->Execute("update documents set parent_id={$this->document_id} where  document_id=" . $id);
        }
    }

}
