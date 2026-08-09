<?php
declare(strict_types=1);

namespace OCA\Charity\Http;

use OCP\AppFramework\Http\ICallbackResponse;
use OCP\AppFramework\Http\IOutput;
use OCP\AppFramework\Http\Response;

class StreamFileResponse extends Response implements ICallbackResponse {
	/** @var resource */
	private $stream;
	private int $offset;
	private int $length;

	/**
	 * @param resource $stream open file handle; the response seeks to the
	 *                         requested offset itself
	 * @param bool $download whether to send Content-Disposition: attachment
	 * @param string|null $filename filename used when downloading
	 */
	public function __construct($stream, int $size, string $mime, string $etag, ?string $range, ?string $ifNoneMatch, bool $download = false, ?string $filename = null) {
		parent::__construct();
		$this->stream = $stream;

		$this->addHeader('Content-Type', $mime);
		if ($download) {
			$name = $filename ?? 'download';
			$name = str_replace(['"', "\r", "\n"], '', $name);
			$this->addHeader('Content-Disposition', "attachment; filename=\"{$name}\"; filename*=UTF-8''" . rawurlencode($name));
		} else {
			$this->addHeader('Content-Disposition', 'inline');
		}
		$this->addHeader('Accept-Ranges', 'bytes');
		$this->addHeader('ETag', '"' . $etag . '"');
		$this->addHeader('Cache-Control', 'private, no-cache');
		$this->addHeader('X-Content-Type-Options', 'nosniff');
		$this->addHeader('Content-Security-Policy', "default-src 'none'");

		$start = 0;
		$end = $size - 1;
		$status = 200;

		if ($ifNoneMatch !== null && trim($ifNoneMatch, '"') === $etag) {
			$this->setStatus(304);
			$this->offset = 0;
			$this->length = 0;
			return;
		}

		if ($range !== null
			&& preg_match('/^bytes=(\d*)-(\d*)$/', trim($range), $m)
			&& ($m[1] !== '' || $m[2] !== '')) {
			if ($m[1] === '') {
				$start = max(0, $size - (int)$m[2]);
				$end = $size - 1;
				$status = 206;
			} else {
				$start = (int)$m[1];
				$end = $m[2] === '' ? $size - 1 : (int)$m[2];
				if ($start >= $size || $start > $end) {
					$this->addHeader('Content-Range', 'bytes */' . $size);
					$this->setStatus(416);
					$this->offset = 0;
					$this->length = 0;
					return;
				}
				$end = min($end, $size - 1);
				$status = 206;
			}
			$this->addHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $size);
		}

		$this->setStatus($status);
		$this->offset = $start;
		$this->length = $end - $start + 1;
		$this->addHeader('Content-Length', (string)$this->length);
	}

	public function callback(IOutput $output): void {
		if ($this->length === 0) {
			return;
		}

		fseek($this->stream, $this->offset);
		$remaining = $this->length;
		while ($remaining > 0 && !feof($this->stream)) {
			$chunk = fread($this->stream, min(8192, $remaining));
			if ($chunk === false || $chunk === '') {
				break;
			}
			$output->setOutput($chunk);
			$remaining -= strlen($chunk);
		}
	}
}
