<?php
namespace OCA\Charity\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class cc_attachment extends Entity implements JsonSerializable {
	protected $objectId;
	protected $objectType;
	protected $description;
	protected $created;
	protected $updated;
	protected $deleted;
	protected $isactive;
	protected $type;
	protected $data;
	protected $url;
	protected $tag;
	protected $size;
	protected $name;
	protected $cmCompanyId;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('objectId', 'integer');
		$this->addType('isactive', 'boolean');
		$this->addType('size', 'integer');
		$this->addType('created', 'datetime');
		$this->addType('updated', 'datetime');
		$this->addType('deleted', 'datetime');
	}

	public function jsonSerialize() {
		return [
			'id' => $this->id,
			'object_id' => $this->objectId,
			'object_type' => $this->objectType,
			'description' => $this->description,
			'created' => $this->created,
			'updated' => $this->updated,
			'deleted' => $this->deleted,
			'isactive' => $this->isactive,
			'type' => $this->type,
			'data' => $this->data,
			'url' => $this->url,
			'tag' => $this->tag,
			'size' => $this->size,
			'name' => $this->name,
			'cmCompanyId' => $this->cmCompanyId,
		];
	}
}
