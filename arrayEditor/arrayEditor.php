<?php

use Nette\Utils\Strings;

class ArrayEditor extends \Nette\Application\UI\Control
{
    private $table;
    private $primaryName;
    private $primaryValue;
    private $arrayField;

    public function attached($presenter)
    {
        parent::attached($presenter);
        //$this->template->setFile($this->getThisTemplatesPath().'/list.latte');
        //$this->template->setFile($this->getListTemplate());
        if (isset($_POST['newGroup'])) {
            $this->inserGroup($_POST['newGroup']);
            $this->redirect('this');
        }
        if (isset($_POST['newParamName'])) {
            foreach ($_POST['newParamName'] as $groupId => $params) {
                foreach ($params as $paramIndex => $paramName) {
                    $paramValue = $_POST['newParamValue'][$groupId][$paramIndex];
                    $this->insertGroupParam($groupId, $paramName, $paramValue);
                }
            }
            $link = $this->link('default');
            header('Location: ' . $link);
        }
    }

    public function handleDefault()
    { }

    public function handleDeleteGroup($groupName)
    {
        $data = $this->getData();
        unset($data[$groupName]);
        $this->storeData($data);
    }

    public function handleDeleteParam($groupId, $paramId)
    {
        $data = $this->getData();
        $groupObject = $data[$groupId];
        $groupObject->delete($paramId);
        $data[$groupId] = $groupObject;
        $this->storeData($data);
    }

    public function setPrimary($primaryName, $primaryValue)
    {
        $this->primaryName = $primaryName;
        $this->primaryValue = $primaryValue;
    }

    public function setTable($tableName)
    {
        $this->table = $tableName;
    }

    public function setArrayField($arrayField)
    {
        $this->arrayField = $arrayField;
    }

    public function getArrayFieldData()
    {
        $val = dibi::select($this->arrayField)->from($this->table)->where("%and", array($this->primaryName => $this->primaryValue))->fetch();
        if ($val) {
            if (!empty($val[$this->arrayField])) {
                return unserialize($val[$this->arrayField]);
            }
        }
        return array();
    }

    public function inserGroup($groupName)
    {
        $data = $this->getData();
        $group = new arrayEditorParamGroup($groupName);
        $data[$group->getId()] = $group;
        $this->storeData($data);
    }

    public function insertGroupParam($groupId, $paramName, $paramValue)
    {

        $data = $this->getData();
        $groupObject = $data[$groupId];
        $groupObject->add($paramName, $paramValue);
        $data[$groupId] = $groupObject;
        $this->storeData($data);
    }

    public function getData()
    {
        return $this->getArrayFieldData();
    }

    public function storeData($data)
    {
        $finalData = array($this->arrayField => serialize($data));
        $val = dibi::update($this->table, $finalData)->where("%and", array($this->primaryName => $this->primaryValue))->execute();
    }

    public function render()
    {

        $this->template->setFile(__DIR__ . '/default.latte');
        $this->template->paramArrayData = $this->getArrayFieldData();
        $this->template->render();
    }
}

class arrayEditorParamGroup
{
    private $name;
    private $id;
    private $params = array();

    public function __construct($name)
    {
        $this->name = $name;
        $this->id = md5($name);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function add($paramName, $paramValue)
    {
        $param = new arrayEditorParamGroupItem($paramName);
        $param->setValue($paramValue);
        $this->params[$param->getId()] = $param;
    }
    public function delete($paramId)
    {
        unset($this->params[$paramId]);
    }

    public function getParams()
    {
        return $this->params;
    }
}

class arrayEditorParamGroupItem
{
    private $name;
    private $id;
    private $value;

    public function __construct($name)
    {
        $this->id = md5($name);
        $this->name = $name;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}
