<?php
/**
 * Frontend template: Giggle Events list.
 *
 * Variables available (injected by Block::render()):
 *
 * @var ExperienceDto[] $experiences  Experience DTOs, already resolved to $language.
 * @var string          $block_title  Optional section heading from the block attribute.
 * @var string          $layout       'carousel' or 'grid'.
 */

declare( strict_types=1 );

use AM\GiggleWp\Dto\ExperienceDto;
use AM\GiggleWp\Support\DateFormatter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -------------------------------------------------------------------------
// Collect schema.org JSON-LD objects for output in <head> (or inline).
// -------------------------------------------------------------------------

$jsonld_items = [];

foreach ( $experiences as $experience ) {
	if ( [] !== $experience->events ) {
		// Output one schema.org Event per scheduled occurrence.
		foreach ( $experience->events as $event ) {
			$ld = [
				'@context'    => 'https://schema.org',
				'@type'       => 'Event',
				'name'        => $experience->title,
				'description' => wp_strip_all_tags( $experience->description ),
				'url'         => $experience->url,
			];

			if ( '' !== $event->startDate ) {
				$ld['startDate'] = $event->startDate;
			}
			if ( '' !== $event->endDate ) {
				$ld['endDate'] = $event->endDate;
			}
			if ( '' !== $experience->imageUrl ) {
				$ld['image'] = $experience->imageUrl;
			}
			if ( '' !== $experience->location ) {
				$ld['location'] = [
					'@type' => 'Place',
					'name'  => $experience->location,
				];
			}
			$ld['organizer'] = [
				'@type' => 'Organization',
				'name'  => 'Giggle.tips',
				'url'   => 'https://giggle.tips',
			];

			$jsonld_items[] = $ld;
		}
	} else {
		// No scheduled events — output as schema.org/Product (service/experience).
		$ld = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => $experience->title,
			'description' => wp_strip_all_tags( $experience->description ),
			'url'         => $experience->url,
			'offers'      => [
				'@type'         => 'Offer',
				'price'         => 0,
				'priceCurrency' => 'EUR',
			],
		];
		if ( '' !== $experience->imageUrl ) {
			$ld['image'] = $experience->imageUrl;
		}
		$jsonld_items[] = $ld;
	}
}
?>

<?php if ( [] !== $jsonld_items ) : ?>
<script type="application/ld+json">
<?php echo wp_json_encode( $jsonld_items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ); ?>
</script>
<?php endif; ?>

<section class="giggle-events giggle-events--<?php echo esc_attr( $layout ); ?> wp-block-giggle-wp-events">

	<?php if ( $block_title ) : ?>
	<h2 class="giggle-events__title"><?php echo esc_html( $block_title ); ?></h2>
	<?php endif; ?>

	<ul class="giggle-events__list">

		<?php foreach ( $experiences as $experience ) :
			$url       = esc_url( $experience->url );
			$image_url = esc_url( $experience->imageUrl );

			$item_data = wp_json_encode( [
				'title'                => $experience->title,
				'description'          => wp_kses( $experience->description, [
					'p'      => [],
					'br'     => [],
					'em'     => [],
					'strong' => [],
					'ul'     => [],
					'ol'     => [],
					'li'     => [],
					'a'      => [ 'href' => [], 'target' => [], 'rel' => [] ],
				] ),
				'imageUrl'             => $experience->imageUrl,
				'url'                  => $experience->url,
				// Meta fields
				'location'             => $experience->location,
				'meetingPoint'         => $experience->meetingPoint,
				'registrationDeadline' => $experience->registrationDeadline,
				'minParticipants'      => $experience->minParticipants,
				'maxParticipants'      => $experience->maxParticipants,
				'duration'             => $experience->duration,
				'durationUnit'         => $experience->durationUnit,
				// Dates
				'events'               => array_map(
					static fn( $event ) => $event->toArray(),
					$experience->events
				),
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
					esc_attr( $experience->title )
				)
				: sprintf(
					' data-giggle-item="%s" aria-label="%s"',
					esc_attr( $item_data ),
					esc_attr( $experience->title )
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
					<h3 class="giggle-event__title"><?php echo esc_html( $experience->title ); ?></h3>

					<?php
					$first_event = $experience->events[0] ?? null;
					$start       = $first_event?->startDate ?? '';
					if ( $start ) :
					?>
					<p class="giggle-event__date">
						<time datetime="<?php echo esc_attr( $start ); ?>"><?php echo esc_html( DateFormatter::format( $start ) ); ?></time>
						<?php if ( count( $experience->events ) > 1 ) : ?>
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
