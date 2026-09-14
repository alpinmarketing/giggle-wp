<?php

declare( strict_types=1 );

namespace AM\GiggleWp\Dto;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A single Giggle experience, already resolved to one preferred-language
 * translation. Built from the raw Giggle API JSON via fromApiItem().
 *
 * Pure PHP domain object — no WordPress function calls, so it stays
 * unit-testable without a running WordPress.
 */
final readonly class ExperienceDto {

	/**
	 * @param EventOccurrenceDto[] $events
	 */
	public function __construct(
		public string $title,
		public string $description,
		public string $imageUrl,
		public string $url,
		public string $location,
		public string $meetingPoint,
		public string $registrationDeadline,
		public ?int $minParticipants,
		public ?int $maxParticipants,
		public ?int $duration,
		public string $durationUnit,
		public array $events,
	) {}

	/**
	 * Map one raw item from the Giggle API `experience/list` response.
	 * Returns null when no title can be resolved for any translation,
	 * mirroring the previous template's skip-if-no-title behaviour.
	 *
	 * @param array<string, mixed> $raw
	 */
	public static function fromApiItem( array $raw, string $preferredLanguage ): ?self {
		$translations = is_array( $raw['translations'] ?? null ) ? $raw['translations'] : [];
		$translation  = self::pickTranslation( $translations, $preferredLanguage );
		$title        = (string) ( $translation['title'] ?? '' );

		if ( '' === $title ) {
			return null;
		}

		$raw_events = is_array( $raw['events'] ?? null ) ? $raw['events'] : [];
		$events     = array_values( array_map(
			static fn( array $event ): EventOccurrenceDto => new EventOccurrenceDto(
				(string) ( $event['startDate'] ?? '' ),
				(string) ( $event['endDate'] ?? '' )
			),
			array_filter( $raw_events, 'is_array' )
		) );

		return new self(
			title: $title,
			description: (string) ( $translation['description'] ?? '' ),
			imageUrl: (string) ( $raw['imageUrl'] ?? '' ),
			url: (string) ( $raw['url'] ?? '' ),
			location: (string) ( $raw['location'] ?? '' ),
			meetingPoint: (string) ( $translation['location'] ?? $translation['meetingPoint'] ?? $raw['meetingPoint'] ?? '' ),
			registrationDeadline: (string) ( $raw['registrationDeadline'] ?? '' ),
			minParticipants: isset( $raw['minParticipants'] ) ? (int) $raw['minParticipants'] : null,
			maxParticipants: isset( $raw['maxParticipants'] ) ? (int) $raw['maxParticipants'] : null,
			duration: isset( $raw['duration'] ) ? (int) $raw['duration'] : null,
			durationUnit: (string) ( $raw['durationUnit'] ?? 'min' ),
			events: $events,
		);
	}

	/**
	 * @param array<int, mixed> $translations
	 * @return array<string, mixed>
	 */
	private static function pickTranslation( array $translations, string $preferred ): array {
		if ( [] === $translations ) {
			return [];
		}

		if ( '' !== $preferred ) {
			foreach ( $translations as $translation ) {
				if ( is_array( $translation ) && ( $translation['language'] ?? null ) === $preferred ) {
					return $translation;
				}
			}
		}

		$first = $translations[ array_key_first( $translations ) ];

		return is_array( $first ) ? $first : [];
	}
}
