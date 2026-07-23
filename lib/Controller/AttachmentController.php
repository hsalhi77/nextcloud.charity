<?php
namespace OCA\Charity\Controller;

use OCA\Charity\Service\AttachmentService;
use OCA\Charity\Service\Helper;
use OCP\IRequest;
use OCP\AppFramework\Controller;

class AttachmentController extends Controller {
    private $service;
    private $helper;
    private $UserId;

    public function __construct($AppName, IRequest $request, AttachmentService $service, Helper $helper, $UserId) {
        parent::__construct($AppName, $request);
        $this->service = $service;
        $this->helper = $helper;
        $this->UserId = $UserId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param string $object_type
     */
    public function index(string $object_type) {
        return $this->helper->handleErrorResponse(function () use ($object_type) {
            return $this->service->findAll($object_type);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param int $object_id
     * @param string $object_type
     */
    public function show(int $object_id, string $object_type) {
        return $this->helper->handleErrorResponse(function () use ($object_id, $object_type) {
            return $this->service->findAll($object_type, $object_id);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function create() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            $objectType = $params['objectType'] ?? '';
            $objectId = (int)($params['objectId'] ?? 0);
            $uploadedFile = $this->request->getUploadedFile('file');
            $this->helper->logger->info('Charity attachment upload received', [
                'app' => 'charity',
                'hasUploadedFile' => $uploadedFile !== null,
                'objectType' => $objectType,
                'objectId' => $objectId,
            ]);

            if ($uploadedFile !== null) {
                $uploadError = (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE);
                if ($uploadError !== UPLOAD_ERR_OK) {
                    throw new \InvalidArgumentException('File upload failed with PHP error code ' . $uploadError);
                }
            }

            $file = [
                'name' => ($uploadedFile['name'] ?? '') ?: ($params['file']['name'] ?? ''),
                'size' => (int)(($uploadedFile['size'] ?? 0) ?: ($params['file']['size'] ?? 0)),
                'tmp_name' => $uploadedFile['tmp_name'] ?? '',
                'type' => ($uploadedFile['type'] ?? '') ?: ($params['file']['type'] ?? ''),
                'tag' => $params['tag'] ?? ($params['file']['tag'] ?? ''),
                'description' => $params['description'] ?? ($params['file']['description'] ?? ''),
                'data' => $params['file']['data'] ?? '',
            ];
            return $this->service->create($objectType, $objectId, $file, $this->UserId);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function chunk() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            $uploadId = $params['uploadId'] ?? '';
            $index = (int)($params['index'] ?? 0);
            $total = (int)($params['total'] ?? 0);
            $uploadedFile = $this->request->getUploadedFile('chunk');
            $this->helper->logger->info('Charity attachment chunk received', [
                'app' => 'charity',
                'uploadId' => $uploadId,
                'index' => $index,
                'total' => $total,
                'hasUploadedFile' => $uploadedFile !== null,
                'uploadedFile' => $uploadedFile,
            ]);
            $this->service->storeChunk($uploadId, $index, $total, $uploadedFile);
            return ['uploadId' => $uploadId, 'index' => $index];
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function finalize() {
        return $this->helper->handleErrorResponse(function () {
            $params = $this->request->getParams();
            $objectType = $params['objectType'] ?? '';
            $objectId = (int)($params['objectId'] ?? 0);
            $uploadId = $params['uploadId'] ?? '';
            $filename = $params['filename'] ?? '';
            $tag = $params['tag'] ?? '';
            $description = $params['description'] ?? '';
            $total = (int)($params['total'] ?? 0);
            $this->helper->logger->info('Charity attachment finalize received', [
                'app' => 'charity',
                'objectType' => $objectType,
                'objectId' => $objectId,
                'uploadId' => $uploadId,
                'filename' => $filename,
                'tag' => $tag,
                'total' => $total,
            ]);
            return $this->service->finalizeUpload($objectType, $objectId, $uploadId, $filename, $tag, $description, $total, $this->UserId);
        });
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @param int $id
     */
    public function destroy(int $id) {
        return $this->helper->handleErrorResponse(function () use ($id) {
            $this->service->delete($id, $this->UserId);
            return true;
        });
    }
}
