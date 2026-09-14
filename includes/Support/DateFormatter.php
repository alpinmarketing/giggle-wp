<?php

declare( strict_types=1 );

namespace AM\GiggleWp\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Formats UTC ISO 8601 date strings from the Giggle API for display.
 * Pure PHP — no WordPress calls — the caller is responsible for escaping.
 */
final class DateFormatter {

	public static function format( string $iso ): string {
		try {
			$dt = new DateTimeImmutable( $iso, new DateTimeZone( 'UTC' ) );

			return $dt->format( 'd.m.Y H:i' );
		} catch ( Exception ) {
			return $iso;
		}
	}
}
