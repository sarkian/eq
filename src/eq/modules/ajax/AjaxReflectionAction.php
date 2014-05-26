<?php

namespace eq\modules\ajax;

use eq\web\HttpException;
use eq\web\ReflectionAction;
use eq\web\route\RouteException;
use EQ;

class AjaxReflectionAction extends ReflectionAction
{

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
//        catch(RouteException $e) {
//            throw new HttpException(400);
//        }
        catch(\Exception $ex) {
            $res->setSuccess(false)
                ->setMessage(EQ_DBG ? $ex->getMessage() : EQ::t("Application error"))
                ->noRaw()->clear()->printResponse();
        }
        return $res;
    }

    protected function getResponseParameter()
    {
        $args = $this->getParameters();
        if(!$args)
            return false;
        $cls = $args[0]->getClass();
        if($cls instanceof \ReflectionClass && $cls->getName() === 'eq\modules\ajax\AjaxResponse')
            return $args[0]->getName();
        return false;
    }

} 