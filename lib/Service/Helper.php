<?php

declare(strict_types=1);

namespace OCA\Charity\Service;

use Closure;
use OCA\Charity\AppInfo\Application;
use OCA\Charity\Exceptions\BadRequestException;
use OCA\Charity\Exceptions\ConflictException;
use OCA\Charity\Exceptions\InsufficientStorageException;
use OCA\Charity\Exceptions\InvalidAttachmentType;
use OCA\Charity\Exceptions\NoPermissionException;
use OCA\Charity\Exceptions\NotFoundException;
use OCA\Charity\Service\Util;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IUserSession;

use Psr\Log\LoggerInterface;

class Helper
{

    /** @var LoggerInterface */
    public $logger;
    /** @var IUserSession */
    private $userSession;

    public function __construct()
    {
    }


    public function handleErrorResponse(Closure $callback)
    {
        try {
            
            $message=array();
            $message["message"]="succes";
            $DataResponse=new DataResponse($callback());
            $message["data"]=$DataResponse->getData();
           return $message;
        } catch (NotFoundException $e) {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
            \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (NoPermissionException $e) {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (BadRequestException $e) {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
            \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (ConflictException $e) {
           $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (InsufficientStorageException $e) {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (InvalidAttachmentType $e) {
           $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (\BadMethodCallException $e)
        {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
            \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (DoesNotExistException $e)
        {
            $message=array();
            $message["message"]=$e->getMessage();
            $message["data"]=[];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (\Throwable $e) {
            $message = [];
            $message["message"] = $e->getMessage();
            $message["data"] = [];
            \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
        }
        catch (\Error $e) {
            $message = [];
            $message["message"] = $e->getMessage();
            $message["data"] = [];
             \OC::$server->getLogger()->error($e->getLine()." ".$e->getTraceAsString());
            return $message;
         }
       

    }
}
