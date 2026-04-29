<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<dialog class="giggle-modal" id="giggle-modal">
	<div class="giggle-modal__inner">

		<button class="giggle-modal__close" id="giggle-modal-close" type="button" aria-label="<?php echo esc_attr( function_exists( 'pll__' ) ? pll__( 'Close' ) : __( 'Close', 'giggle-wp' ) ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" width="18" height="18" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
			</svg>
		</button>

		<div class="giggle-modal__scroll">
			<div class="giggle-modal__image-wrap" id="giggle-modal-image-wrap">
				<img class="giggle-modal__image" id="giggle-modal-image" src="" alt="" />
			</div>
			<div class="giggle-modal__body">
				<h2 class="giggle-modal__title" id="giggle-modal-title"></h2>
				<div class="giggle-modal__dates" id="giggle-modal-dates"></div>
				<dl class="giggle-modal__meta" id="giggle-modal-meta"></dl>
				<div class="giggle-modal__description" id="giggle-modal-description"></div>
			</div>
		</div>

		<div class="giggle-modal__footer">
			<a class="giggle-modal__cta" id="giggle-modal-cta" href="" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( function_exists( 'pll__' ) ? pll__( 'Learn more & book' ) : __( 'Learn more & book', 'giggle-wp' ) ); ?>
			</a>
		</div>

	</div>
</dialog>
