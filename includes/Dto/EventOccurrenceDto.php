<?php

declare( strict_types=1 );

namespace AM\GiggleWp\Dto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single scheduled occurrence of an experience.
 */
final readonly class EventOccurrenceDto {

	public function __construct(
		public string $startDate,
		public string $endDate,
	) {}

	/**
	 * @return array{startDate: string, endDate: string}
	 */
	public function toArray(): array {
		return [
			'startDate' => $this->startDate,
			'endDate'   => $this->endDate,
		];
	}
}
