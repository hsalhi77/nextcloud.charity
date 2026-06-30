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

use OCP\AppFramework\Http;
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


    public function handleErrorResponse(Closure $callback): JSONResponse {
        try {
            return new JSONResponse([
                'message' => 'succes',
                'data' => $callback(),
            ], Http::STATUS_OK);
        } catch (NotFoundException $e) {
            return $this->buildErrorResponse($e, $e->getStatus());
        } catch (NoPermissionException $e) {
            return $this->buildErrorResponse($e, $e->getStatus());
        } catch (BadRequestException $e) {
            return $this->buildErrorResponse($e, $e->getStatus());
        } catch (ConflictException $e) {
            return $this->buildErrorResponse($e, $e->getStatus());
        } catch (InsufficientStorageException $e) {
            return $this->buildErrorResponse($e, Http::STATUS_INSUFFICIENT_STORAGE);
        } catch (InvalidAttachmentType $e) {
            return $this->buildErrorResponse($e, Http::STATUS_BAD_REQUEST);
        } catch (\BadMethodCallException $e) {
            return $this->buildErrorResponse($e, Http::STATUS_BAD_REQUEST);
        } catch (DoesNotExistException $e) {
            return $this->buildErrorResponse($e, Http::STATUS_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->buildErrorResponse($e, Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    private function buildErrorResponse(\Throwable $e, int $status): JSONResponse {
        \OC::$server->getLogger()->error($e->getLine() . ' ' . $e->getTraceAsString());
        return new JSONResponse([
            'message' => $e->getMessage(),
            'data' => [],
        ], $status);
    }
}
