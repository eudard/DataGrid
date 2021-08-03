<?php

/**
 *
 *
 public function PricesGrid($id_ship) {

        //$id_ship = $this->getParameter('id_ship');
        $source = \dibi::select('*')->from('icard_shop_ships_prices')->where("%and",array("id_ship"=>$id_ship));

        $grid = new DataGrid();
        $grid->setGridName('Cena dopravy');
        $grid->source($source);
        $grid->setPid('id_ship_price');
        $grid->order(array("id_ship_price" => "ASC"));
        $grid->limit(100);

        //show data
        //$grid->col('id_ship_price','ID');
        $grid->col('ship_valid_from_price','Cena od',function($val,$row) {
            return $val*1;
        });
        $grid->col('ship_valid_to_price','Cena do',function($val,$row) {
            if (empty($val)) { return "jakákoli"; }
            return $val;
        });
        $grid->col('ship_valid_from_weight','Váha od',function($val,$row) {
            return $val*1;
        });
        $grid->col('ship_valid_to_weight','Váha do',function($val,$row) {
            if (empty($val)) { return "jakákoli"; }
            return $val;
        });
        $grid->col('ship_country_code','Země');
        //$grid->col('ship_tax_percent','DPH %');
        $grid->col('ship_price','Price');

        $grid->col('akce','Akce',function($val, $row) use ($grid) {
            return $grid->getActionButtons($row);
        });

        $structure = [
            't1'=>['type'=>'group','caption'=>'Cena dopravy'],
            'id_ship'=>['type'=>'static','value'=>$id_ship],
            'ship_price'=>['type'=>'string','caption'=>'Cena vč.DPH'],
            'ship_tax_percent'=>['type'=>'string','caption'=>'DPH %','default'=>21],
            'ship_country_code'=>['type'=>'select','caption'=>'Stát','data'=>['CZ'=>'Česko']],
            't3'=>['type'=>'group','caption'=>'Platnost dopravy','desc'=>'Podmínky za kterých se nabídne tento typ dopravy'],
            'ship_valid_from_price'=>['type'=>'float','caption'=>'Cena od'],
            'ship_valid_to_price'=>['type'=>'float','caption'=>'Cena do'],
            'ship_valid_from_weight'=>['type'=>'float','caption'=>'Váha od'],
            'ship_valid_to_weight'=>['type'=>'float','caption'=>'Váha do'],

        ];

        //new data
        $grid->new('icard_shop_ships_prices',$structure);

        //edit data
        $grid->edit('icard_shop_ships_prices',$structure);

        return $grid;
    }

    public function PaymentsGrid($name) {
        $root_id_icard = $this->website->getCurrentPage()->get('root_id_icard');
        $source = \dibi::select('*')->from('icard_shop_ships')->where('%and',array("id_icard"=>$root_id_icard));
        $uploadPath = WWW_DIR;
        $grid = new DataGrid();
        $grid->setGridName('Doprava');
        $grid->source($source);
        $grid->setPid('id_ship');
        $grid->order(array("id_ship" => "ASC"));
        $grid->limit(100);

        //show data
        //$grid->col('id_ship','ID');


        $grid->col('ship_name','Název dopravce');

        $grid->col('ship_logo','Logo',function ($val,$row) use ($uploadPath) {

            $fileRealPath = $uploadPath."/".$val;
            $filePath = str_replace(WWW_DIR,"",$fileRealPath);

            if (file_exists($fileRealPath) && !empty($val)) {
                $el = Html::el('img')->src($filePath);
                return $el;
            }

        });


        $_this = $this;
        $grid->col('subgrid','Ceník dopravy',function ($val,$row) use ($_this,$grid) {
            $id = $grid->getPid($row);
            $_this->getComponent("pricesGrid-".$id)->render(); //recursive grid component magic!
        });

        $grid->col('platba','Možnosti platby',function ($val,$row) {
            $el = Html::el();
            if($row->allow_payment_cash) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("hotově"));
            }
            if($row->allow_payment_cash_ondelivery) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("dobírka"));
            }
            if($row->allow_payment_bank_transfer) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("převod"));
            }
            if($row->allow_payment_card_onplace) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("karta"));
            }
            if($row->allow_payment_gopay) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("GoPay"));
            }
            if($row->allow_payment_payu) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("PayU"));
            }
            if($row->allow_payment_payout) {
                $el -> add(Html::el("span")->class("dgrid-tag")->setHtml("PayOut"));
            }
            return $el;
        });




        $grid->col('akce','',function ($val,$row) use ($grid) {
            return $grid->getActionButtons($row);
        });


        $structure = [
            't1'=>['type'=>'group','caption'=>'Parametry přepravy'],

            'id_icard'=>['type'=>'static','value'=>$this->website->getRootId()],
            'ship_name'=>['type'=>'string','caption'=>'Název'],
            'ship_desc'=>['type'=>'string','caption'=>'Popis'],
            'ship_api'=>['type'=>'select','caption'=>'API','data'=>['default'=>'Standard','zasilkovna'=>'Zásilkovna']],
            'ship_logo'=>['type'=>'image','caption'=>'Logo','upload_dir'=>$uploadPath,'size'=>['width'=>'100','height'=>'50']],

            't2'=>['type'=>'group','caption'=>'Možnosti platby'],

            'allow_payment_cash'=>['type'=>'switch','caption'=>'Hotovost'],
            'allow_payment_cash_ondelivery'=>['type'=>'switch','caption'=>'Dobírka'],
            'allow_payment_bank_transfer'=>['type'=>'switch','caption'=>'Bankovní převod'],
            'allow_payment_card_onplace'=>['type'=>'switch','caption'=>'Kartou na pokladně'],
            'allow_payment_gopay'=>['type'=>'switch','caption'=>'On-line platba GoPay'],
            'allow_payment_payu'=>['type'=>'switch','caption'=>'On-line platba PayU'],
            'allow_payment_payout'=>['type'=>'switch','caption'=>'On-line platba PayOut'],
        ];

        //new data
        $grid->new('icard_shop_ships',$structure);

        //edit data
        $grid->edit('icard_shop_ships',$structure);

        return $grid;
    }

    public function createComponentPaymentsGrid($name)
    {
        $_this = $this;
        $control = new Multiplier(function ($name) use ($_this) {
            return $_this->PaymentsGrid($name);
        });
        return $control;
    }

    public function createComponentPricesGrid($name)
    {
        $_this = $this;
        $control = new Multiplier(function ($id) use ($_this) {
            return $_this->PricesGrid($id);
        });
        return $control;

    }
 */

namespace ICARD;

use dibi;
use DibiConnection;
use Mpdf\Tag\Q;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\ArrayHash;
use Nette\Utils\Html;
use stdClass;

class DataGrid extends \Nette\Application\UI\Control
{
    private $dataSource;
    private $pid;
    private $gridName;
    private $table;
    private $itemPerPage = 20;
    private $defaultOrder;
    private $skin = 'basic';
    private $cols;
    private $newItemTable;
    private $newItemStructure;
    private $insertCallbacks;

    private $editItemTable;
    private $editItemStructure;
    private $updateCallbacks;

    private $deleteCallbacks;

    private $view = 'grid';
    private $testMode = false;
    private $formData;
    /** @persistent */
    public $id;

    public function attached($presenter)
    {
        parent::attached($presenter);
        $this->template->uid  = $this->getName();
        //dump($this->template->uid);
    }

    public function source($dataSource)
    {
        $this->dataSource = $dataSource;
    }

    /**
     * @var DibiConnection
     */
    public function connection()
    {
        return $this->dataSource->getConnection();
    }

    public function setGridName($gridName)
    {
        $this->gridName = $gridName;
    }
    public function getGridName()
    {
        return $this->gridName;
    }

    public function order($defaultOrderArray)
    {
        $this->defaultOrder = $defaultOrderArray;
    }

    public function limit($itemPerPage)
    {
        $this->itemPerPage = $itemPerPage;
    }

    public function getPid($row)
    {
        if (isset($row[$this->pid])) {
            return $row[$this->pid];
        }
    }

    public function setPid($primaryIdName)
    {
        $this->pid = $primaryIdName;
    }

    public function col($colName, $colCaption, $colCallback = NULL)
    {
        $this->cols[] = new ICardDataGridCol($colName, $colCaption, $colCallback);
    }

    public function new($table, $newItemStructure)
    {
        $this->newItemTable = $table;
        $this->newItemStructure = $newItemStructure;
    }

    public function edit($table, $editItemStructure)
    {
        $this->editItemTable = $table;
        $this->editItemStructure = $editItemStructure;
    }

    public function inAddAllowed()
    {
        if (is_null($this->newItemStructure)) {
            return false;
        }
        return true;
    }

    private function getThisTemplate()
    {
        return __DIR__ . "/skins/" . $this->skin . "/grid.latte";
    }

    public function getData()
    {
        return $this->dataSource->fetchAll();
    }

    public function getCols()
    {
        return $this->cols;
    }

    public function handleDelete($id)
    {

        //fake form with NULL values
        $form = new stdClass;
        $values = new ArrayHash;
        foreach ($this->editItemStructure as $fieldname => $fielddata) {
            $values[$fieldname] = NULL;
        }
        $form->values = $values;
        $this->collectFormData($form, true);

        //process delete callbacks
        if (!is_null($this->deleteCallbacks)) {
            foreach ($this->deleteCallbacks as $cb) {
                //$allData = $form->values;
                $cb(array($this->pid => $id));
            }
        }

        $this->connection()->delete($this->editItemTable)->where("%and", array(
            $this->pid => $id
        ))->execute();

        $this->redirect('this');
    }

    public function handleEdit($id)
    {
        $this->view = 'form';
        $this->template->formName = 'editForm';
        $this->template->formStructure = $this->editItemStructure;
        if (!$this->getParameter('id')) {
            $this->view = 'grid';
        }
    }

    public function handleAdd()
    {
        $this->view = 'form';
        $this->template->formName = 'addForm';
        $this->template->formStructure = $this->newItemStructure;
    }

    public function render()
    {
        $latte = new \Nette\Latte\Engine;
        $set = new \Nette\Latte\Macros\MacroSet($latte->compiler);
        $set->addMacro(
            'inject', // název makra
            'echo $control->injectAsset(%node.word)',  // PHP kód nahrazující otevírací značku
            NULL // kód nahrazující uzavírací značku
        );
        $this->template->registerFilter($latte);
        $temlateFile = __DIR__ . "/skins/" . $this->skin . "/" . $this->view . ".latte";
        $this->template->setFile($temlateFile);
        return $this->template->render();
    }

    public function injectAsset($file)
    {
        $path = __DIR__ . $file;
        $ext = explode(".", $file);
        $ext = end($ext);

        if (file_exists($path)) {
            switch ($ext) {
                case "css":
                    return "<style>" . file_get_contents($path) . "</style>";
                    break;
            }
        }


        //render file to template
        //return $path;
    }


    public function createComponentEditForm($name)
    {
        $form = new Form($this, $name);
        $ds = clone ($this->dataSource);
        /*$defaults = $this->connection()->select("*")->from($this->editItemTable)->where("%and", array(
            $this->pid => $this->getParameter('id')
        ))->fetch();*/

        $defaults = $ds->where("%and", array(
            $this->pid => $this->getParameter('id')
        ))->fetch();

        $this->generateFormFrom($this->editItemStructure, $form, $defaults);
        $form->addSubmit("send", "Upravit");
        $form->onSuccess[] = array($this, 'EditFormSubmitted');
        //$form->setDefaults($defaults);
        return $form;
    }

    public function EditFormSubmitted($form)
    {
        $pid = $this->getParameter('id');
        $data = $this->collectFormData($form, true);
        if ($this->testMode) {
            $this->connection()->update($this->newItemTable, $data)->where("%and", array(
                $this->pid => $pid
            ))->test();
        } else {
            $this->connection()->update($this->newItemTable, $data)->where("%and", array(
                $this->pid => $pid
            ))->execute();
        }

        if (!is_null($this->updateCallbacks)) {
            $allData = $form->values;
            $pid_key = $this->pid;
            $allData->{$pid_key} = $pid;
            foreach ($this->updateCallbacks as $cb) {
                $cb($allData, array($this->pid => $pid));
            }
        }
        $this->redirect('this');
    }

    public function createComponentAddForm($name)
    {

        $defaultValues = [];
        foreach ($this->newItemStructure as $item_name => $item_options) {
            if (isset($item_options['default'])) {
                $defaultValues[$item_name] = $item_options['default'];
            }
        }

        $form = new Form($this, $name);
        $this->generateFormFrom($this->newItemStructure, $form, $defaultValues);
        $form->addSubmit("send", "Nová položka");
        $form->onSuccess[] = array($this, 'AddFormSubmitted');


        //$form->setDefaults($defaultValues);

        return $form;
    }


    public function AddFormSubmitted($form)
    {
        $data = $this->collectFormData($form);
        if ($this->testMode) {
            $this->connection()->insert($this->newItemTable, $data)->test();
        } else {
            $this->connection()->insert($this->newItemTable, $data)->execute();
        }
        $insertedId = $this->connection()->getInsertId();
        if (!is_null($this->insertCallbacks)) {
            foreach ($this->insertCallbacks as $cb) {
                $pid = $this->pid;
                $allData = $form->values;
                $allData->$pid = $insertedId;
                $cb($allData);
            }
        }

        $this->redirect('this');
    }

    public function addInsertCallback($function)
    {
        $this->insertCallbacks[] = $function;
    }

    public function addUpdateCallback($function)
    {
        $this->updateCallbacks[] = $function;
    }

    public function addDeleteCallback($function)
    {
        $this->deleteCallbacks[] = $function;
    }




    public function getNewItemStructure()
    {
        return $this->newItemStructure;
    }

    public function getEditLink($row)
    {
        return $this->link('Edit', array($this->getPid($row)));
    }

    public function getDeleteLink($row)
    {
        return $this->link('Delete', array($this->getPid($row)));
    }

    private function collectFormData($form, $dataForUpdate = false)
    {
        $data = [];
        foreach ($this->newItemStructure as $col_name => $col_option) {
            $data[$col_name] = isset($form->values[$col_name]) ? $form->values[$col_name] : NULL;
            if ($col_option['type'] == 'date_timestamp') {
                $data[$col_name] = strtotime($form->values[$col_name]);
            }
            if ($col_option['type'] == 'float') {
                if (empty($form->values[$col_name])) {
                    $data[$col_name] = NULL;
                    if (isset($col_option['default'])) {
                        $data[$col_name] = $col_option['default'];
                    }
                } else {
                    $data[$col_name] = $form->values[$col_name] * 1;
                }
            }
            if ($col_option['type'] == 'static') {
                if (is_callable($col_option['value'])) {
                    $fn = $col_option['value'];
                    $data[$col_name] = $fn($form->values);
                } else {
                    $data[$col_name] = $col_option['value'];
                }
            }
            if ($col_option['type'] == 'switch') {
                $data[$col_name] = $form->values[$col_name] ? 1 : 0;
            }
            if ($col_option['type'] == 'switchnumber') {
                $isOn = $form->values[$col_name] ? true : false;
                if ($isOn) {
                    $coinput = $col_name . "_input";
                    $data[$col_name] = $form->values[$coinput] * 1;
                } else {
                    $data[$col_name] = NULL;
                }
            }
            if ($col_option['type'] == 'group') {
                unset($data[$col_name]);
            }

            /* MtoN relation behavior */
            if ($col_option['type'] == 'selectMtoN') {
                $this->addInsertCallback(function ($data) use ($col_option) {
                    $insertData = [];
                    foreach ($col_option['insertFields'] as $field) {
                        $insertData[$field] = $data->$field;
                    }
                    $this->connection()->insert($col_option['table'], $insertData)->execute();
                    //$this->getPresenter()->flashMessage(dibi::$sql);
                });
                $this->addUpdateCallback(function ($data, $pid) use ($col_option) {
                    $is_there = $this->connection()->select("*")->from($col_option['table'])->where("%and", $pid)->fetch();
                    if ($is_there) {
                        //update
                        if (!empty($col_option['updateFields'])) {
                            $updateData = [];
                            foreach ($col_option['updateFields'] as $field) {
                                $updateData[$field] = $data->$field;
                            }
                            $this->connection()->update($col_option['table'], $updateData)
                                ->where('%and', $pid)
                                ->execute();
                            //$this->getPresenter()->flashMessage(dibi::$sql);
                        }
                    } else {
                        //insert
                        if (!empty($col_option['insertFields'])) {
                            $insertData = [];
                            foreach ($col_option['insertFields'] as $field) {
                                $insertData[$field] = $data->$field;
                            }

                            $this->connection()->insert($col_option['table'], $insertData)
                                ->execute();
                            //$this->getPresenter()->flashMessage(dibi::$sql);
                        }
                    }
                });
                $this->addDeleteCallback(function ($pid) use ($col_option) {
                    $this->connection()->delete($col_option['table'])
                        ->where('%and', $pid)
                        ->execute();
                    //$this->getPresenter()->flashMessage(dibi::$sql);
                });

                unset($data[$col_name]);
            }
            /* password behavior */
            if ($col_option['type'] == 'password') {
                if ($dataForUpdate && empty($form->values[$col_name])) {
                    //if empty in update mode - disable reset
                    unset($data[$col_name]);
                } else {
                    if (isset($col_option['cryptoFunction'])) {
                        $fn = $col_option['cryptoFunction'];
                        $data[$col_name] = $fn($form->values);
                    } else {
                        $data[$col_name] = $form->values[$col_name];
                    }
                    if (empty($data[$col_name])) {
                        $data[$col_name] = 'password empty error';
                    }
                }
            }
            if ($col_option['type'] == 'image') {
                $file = $data[$col_name];
                if ($file->isOK()) {
                    $fname = $file->getName();

                    $ext = explode(".", $fname);
                    $ext = end($ext);

                    $hash = md5(time() . "_" . $file->getName());
                    //filename
                    $filename = $hash . "." . $ext;
                    if (isset($col_option['upload_dir'])) {
                        $filePath = $col_option['upload_dir'] . "/" . $filename;
                        if (isset($col_option['size'])) {
                            $image = $file->toImage();
                            $width = isset($col_option['size']['width']) ? $col_option['size']['width'] : 200;
                            $height = isset($col_option['size']['height']) ? $col_option['size']['height'] : 200;
                            $quality = 90;
                            $image->resize($width, $height);
                            $image->save($filePath, $quality);
                        } else {
                            $file->move($filePath);
                        }
                        $data[$col_name] = $filename;
                    }
                } else {
                    unset($data[$col_name]);
                }
            }
        }
        //dump($data);exit;
        return $data;
    }

    private function generateFormFrom($structure, $form, $defaults = [])
    {
        $setDefaults = [];
        foreach ($structure as $col_name => $col_option) {
            switch ($col_option['type']) {
                case 'string':
                    $form->addText($col_name, $col_option['caption']);
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$col_name] = $defaults[$col_name];
                    }
                    break;
                case 'password':
                    $form->addPassword($col_name, $col_option['caption']);
                    break;
                case 'date_timestamp':
                    $form->addText($col_name, $col_option['caption']);
                    if (isset($defaults[$col_name])) {
                        if (is_numeric($defaults[$col_name])) {
                            $setDefaults[$col_name] = date('Y-m-d', $defaults[$col_name]);
                        } else {
                            $setDefaults[$col_name] = date('Y-m-d', strtotime($defaults[$col_name]));
                        }
                    }
                    break;
                case 'percentage':
                    $form->addText($col_name, $col_option['caption']);
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$col_name] = $defaults[$col_name];
                    }
                    break;
                case 'static':
                case 'group':
                    //self added in Submitted method
                    break;
                case 'switch':
                    $form->addCheckbox($col_name, $col_option['caption']);
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$col_name] = $defaults[$col_name];
                    }
                    break;
                case 'switchnumber':
                    $form->addCheckbox($col_name, $col_option['caption']);
                    $coinput = $col_name . "_input";
                    $form->addText($coinput, '');
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$coinput] = $defaults[$col_name];
                        $setDefaults[$col_name] = 1;
                    } else {
                        $setDefaults[$coinput] = $col_option['valueDefault'];
                    }

                    break;

                case 'selectMtoN':
                case 'select':
                    $form->addSelect($col_name, $col_option['caption'], $col_option['data']);
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$col_name] = $defaults[$col_name];
                    }
                    break;
                case 'image':
                    $form->addUpload($col_name, $col_option['caption']);
                    break;
                default:
                    $form->addText($col_name, $col_option['caption']);
                    if (isset($defaults[$col_name])) {
                        $setDefaults[$col_name] = $defaults[$col_name];
                    }
                    break;
            }
            if (isset($form[$col_name]) && isset($col_option['required']) && $col_option['required'] == true) {
                $form[$col_name]->setRequired('!');
            }
        }
        //dump($defaults, $setDefaults);
        $form->setDefaults($setDefaults);
    }

    public function getActionButtons($row)
    {
        $el = Html::el('div')->class('dgrid-ar');
        $editLink = Html::el('a')->href($this->getEditLink($row))->class('dgrid-btn')->setHtml('upravit');
        $deleteLink = Html::el('a')->href($this->getDeleteLink($row))->class('dgrid-btn dgrid-btn-danger')->setHtml('&times;');
        $el->add($editLink);
        $el->add($deleteLink);
        return $el;
    }


    /*public function PricesGrid($name) {
        $source = \dibi::select('*')->from('icard_shop_ships_prices');

        $grid = new DataGrid();
        $grid->source($source);
        $grid->setPid('id_ship_price');
        $grid->order(array("id_ship_price" => "ASC"));
        $grid->limit(100);

        //show data
        $grid->col('id_ship_price','ID');
        $grid->col('ship_price','Price');

        $structure = [
            'ship_price'=>['type'=>'string','caption'=>'Popis'],
        ];

        //new data
        $grid->new('icard_shop_ships_prices',$structure);

        //edit data
        $grid->edit('icard_shop_ships_prices',$structure);

        return $grid;
    }

    public function createComponentPaymentsGrid($name)
    {
        $_this = $this;
        $control = new Multiplier(function ($name) use ($_this) {
            return $_this->PricesGrid($name);
        });
        return $control;
    }*/
}
