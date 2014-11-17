<?php

namespace eq\modules\ajax;

use eq\web\HttpException;
use eq\web\ReflectionAction;
use eq\web\route\RouteException;
use EQ;

class AjaxReflectionAction extends ReflectionAction
{

    protected $_response_parameter = null;

    public function __construct($cname, $name)
    {
        $controller = new $cname();
        parent::__construct($controller, $name);
    }

    public function call($args = [])
    {
        $res_param = $this->getResponseParameter();
        if(!$res_param)
            throw new HttpException(404);
        $res = AjaxResponse::instance();
        $args[$res_param] = AjaxResponse::instance();
        try {
            $res->processReturnValue(parent::call($args))->printResponse();
        }
        catch(AjaxErrorException $e) {
            $res->printResponse();
        }
        catch(\Exception $ex) {
            if($res->isRaw())
                throw $ex;
            else {
                if($ex instanceof HttpException)
                    EQ::app()->header->status($ex->getStatus(), $ex->getMessage());
                $res->setSuccess(false)
                    ->setMessage(EQ_DBG ? $ex->getMessage() : EQ::t("Application error"))
                    ->noRaw()->clear()->printResponse();
            }
        }
        return $res;
    }

    public function argDocType($name)
    {
        return $name === $this->getResponseParameter() ? "" : parent::argDocType($name);
    }

    protected function getResponseParameter()
    {
        if($this->_response_parameter === null) {
            $args = $this->getParameters();
            if($args) {
                $cls = $args[0]->getClass();
                $this->_response_parameter = $cls instanceof \ReflectionClass
                            && $cls->getName() === 'eq\modules\ajax\AjaxResponse'
                    ? $args[0]->getName() : false;
            }
            else
                $this->_response_parameter = false;
        }
        return $this->_response_parameter;
    }

}