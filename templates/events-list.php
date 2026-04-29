<?php
/**
 * Frontend template: Giggle Events list.
 *
 * Variables available (injected by Giggle_Block::render()):
 *
 * @var array  $items        Array of experience objects from the Giggle API.
 * @var string $block_title  Optional section heading from the block attribute.
 * @var string $language     ISO language code selected in the block (may be empty).
 * @var string $layout       'carousel' or 'grid'.
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------
// Helper: pick the best translation for a given language preference.
// -------------------------------------------------------------------------

/**
 * @param array  $translations  Array of { language, title, description, ... }.
 * @param string $preferred     ISO code (e.g. 'en'). Falls back to first item.
 * @return array
 */
if ( ! function_exists( 'giggle_pick_translation' ) ) :
function giggle_pick_translation( array $translations, string $preferred ): array {
	if ( empty( $translations ) ) {
		return [];
	}

	if ( $preferred ) {
		foreach ( $translations as $t ) {
			if ( isset( $t['language'] ) && $t['language'] === $preferred ) {
				return $t;
			}
		}
	}

	// Fall back to the first available translation.
	return $translations[0];
}
endif;

// -------------------------------------------------------------------------
// Helper: format a UTC ISO 8601 date string for display.
// -------------------------------------------------------------------------

if ( ! function_exists( 'giggle_format_date' ) ) :
function giggle_format_date( string $iso ): string {
	try {
		$dt = new DateTimeImmutable( $iso, new DateTimeZone( 'UTC' ) );
		return $dt->format( 'd.m.Y H:i' );
	} catch ( Exception ) {
		return esc_html( $iso );
	}
}
endif;

// -------------------------------------------------------------------------
// Collect schema.org JSON-LD objects for output in <head> (or inline).
// -------------------------------------------------------------------------

$jsonld_items = [];

foreach ( $items as $exp ) {
	$trans = giggle_pick_translation( $exp['translations'] ?? [], $language );
	$title = $trans['title'] ?? '';
	$desc  = $trans['description'] ?? '';
	$loc   = $trans['location'] ?? '';

	$events = $exp['events'] ?? [];

	if ( ! empty( $events ) ) {
		// Output one schema.org Event per scheduled occurrence.
		foreach ( $events as $event ) {
			$ld = [
				'@context'    => 'https://schema.org',
				'@type'       => 'Event',
				'name'        => $title,
				'description' => wp_strip_all_tags( $desc ),
				'url'         => $exp['url'] ?? '',
			];

			if ( ! empty( $event['startDate'] ) ) {
				$ld['startDate'] = $event['startDate'];
			}
			if ( ! empty( $event['endDate'] ) ) {
				$ld['endDate'] = $event['endDate'];
			}
			if ( ! empty( $exp['imageUrl'] ) ) {
				$ld['image'] = $exp['imageUrl'];
			}
			if ( $loc ) {
				$ld['location'] = [
					'@type' => 'Place',
					'name'  => $loc,
				];
			}
			$ld['organizer'] = [
				'@type' => 'Organization',
				'name'  => 'Giggle.tips',
				'url'   => 'https://giggle.tips',
			];

			$jsonld_items[] = $ld;
		}
	} elseif ( $title ) {
		// No scheduled events — output as schema.org/Product (service/experience).
		$ld = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $title,
			'description' => wp_strip_all_tags( $desc ),
			'url'         => $exp['url'] ?? '',
			'offers'      => [
				'@type'         => 'Offer',
				'price'         => 0,
				'priceCurrency' => 'EUR',
			],
		];
		if ( ! empty( $exp['imageUrl'] ) ) {
			$ld['image'] = $exp['imageUrl'];
		}
		$jsonld_items[] = $ld;
	}
}
?>

<?php if ( ! empty( $jsonld_items ) ) : ?>
<script type="application/ld+json">
<?php echo wp_json_encode( $jsonld_items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
</script>
<?php endif; ?>

<section class="giggle-events giggle-events--<?php echo esc_attr( $layout ); ?> wp-block-giggle-wp-events">

	<?php if ( $block_title ) : ?>
	<h2 class="giggle-events__title"><?php echo esc_html( $block_title ); ?></h2>
	<?php endif; ?>

	<ul class="giggle-events__list">

		<?php foreach ( $items as $exp ) :
			$trans     = giggle_pick_translation( $exp['translations'] ?? [], $language );
			$title     = $trans['title'] ?? '';
			$url       = esc_url( $exp['url'] ?? '' );
			$image_url = esc_url( $exp['imageUrl'] ?? '' );
			$events    = $exp['events'] ?? [];

			if ( ! $title ) {
				continue;
			}

			$item_data = wp_json_encode( [
				'title'                => $title,
				'description'          => wp_kses( $trans['description'] ?? '', [
					'p'      => [],
					'br'     => [],
					'em'     => [],
					'strong' => [],
					'ul'     => [],
					'ol'     => [],
					'li'     => [],
					'a'      => [ 'href' => [], 'target' => [], 'rel' => [] ],
				] ),
				'imageUrl'             => $exp['imageUrl'] ?? '',
				'url'                  => $exp['url'] ?? '',
				// Meta fields
				'location'             => $exp['location'] ?? '',
				'meetingPoint'         => $trans['location'] ?? $trans['meetingPoint'] ?? $exp['meetingPoint'] ?? '',
				'registrationDeadline' => $exp['registrationDeadline'] ?? '',
				'minParticipants'      => $exp['minParticipants'] ?? null,
				'maxParticipants'      => $exp['maxParticipants'] ?? null,
				'duration'             => $exp['duration'] ?? null,
				'durationUnit'         => $exp['durationUnit'] ?? 'min',
				// Dates
				'events'               => array_map( static fn( $e ) => [
					'startDate' => $e['startDate'] ?? '',
					'endDate'   => $e['endDate'] ?? '',
				], $events ),
			] );

			if ( false === $item_data ) {
				$item_data = '{}';
			}

			$tag   = $url ? 'a' : 'div';
			$attrs = $url
				? sprintf(
					' href="%s" target="_blank" rel="noopener noreferrer" data-giggle-item="%s" aria-label="%s"',
					$url,
					esc_attr( $item_data ),
					esc_attr( $title )
				)
				: sprintf(
					' data-giggle-item="%s" aria-label="%s"',
					esc_attr( $item_data ),
					esc_attr( $title )
				);
		?>
		<li class="giggle-events__item">
			<<?php echo ( 'a' === $tag ) ? 'a' : 'div'; ?> class="giggle-event"<?php echo $attrs; ?>>

				<?php if ( $image_url ) : ?>
				<div class="giggle-event__image-wrap">
					<img
						class="giggle-event__image"
						src="<?php echo $image_url; ?>"
						alt=""
						loading="lazy"
					/>
				</div>
				<?php endif; ?>

				<div class="giggle-event__body">
					<h3 class="giggle-event__title"><?php echo esc_html( $title ); ?></h3>

					<?php
					$first_event = $events[0] ?? null;
					$start       = $first_event['startDate'] ?? '';
					if ( $start ) :
					?>
					<p class="giggle-event__date">
						<time datetime="<?php echo esc_attr( $start ); ?>"><?php echo esc_html( giggle_format_date( $start ) ); ?></time>
						<?php if ( count( $events ) > 1 ) : ?>
						<span class="giggle-event__date-more">&hellip;</span>
						<?php endif; ?>
					</p>
					<?php endif; ?>
				</div>

			</<?php echo ( 'a' === $tag ) ? 'a' : 'div'; ?>>
		</li>
		<?php endforeach; ?>

	</ul><!-- .giggle-events__list -->

	<?php if ( 'carousel' === $layout ) : ?>
	<div class="giggle-events__arrows">
		<button class="giggle-events__arrow giggle-events__arrow--prev is-disabled" type="button" aria-label="<?php echo esc_attr( function_exists( 'pll__' ) ? pll__( 'Previous slide' ) : __( 'Previous slide', 'giggle-wp' ) ); ?>" disabled>
			<svg viewBox="0 0 40 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M40 10H1M1 10L10 1M1 10L10 19" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
		<button class="giggle-events__arrow giggle-events__arrow--next" type="button" aria-label="<?php echo esc_attr( function_exists( 'pll__' ) ? pll__( 'Next slide' ) : __( 'Next slide', 'giggle-wp' ) ); ?>">
			<svg viewBox="0 0 40 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M0 10H39M39 10L30 1M39 10L30 19" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</button>
	</div>
	<?php endif; ?>

</section><!-- .giggle-events -->
