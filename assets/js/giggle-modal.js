( function () {

	/* ------------------------------------------------------------------
	   Helpers
	------------------------------------------------------------------ */

	function fmtDate( iso ) {
		if ( ! iso ) return '';
		var d = new Date( iso );
		if ( isNaN( d.getTime() ) ) return iso;
		return d.toLocaleDateString(
			document.documentElement.lang || undefined,
			{ day: '2-digit', month: '2-digit', year: 'numeric' }
		);
	}

	/* ------------------------------------------------------------------
	   Detail modal
	------------------------------------------------------------------ */

	var modal    = document.getElementById( 'giggle-modal' );
	var closeBtn = document.getElementById( 'giggle-modal-close' );

	if ( modal && closeBtn ) {

		function openModal( data ) {
			var i18n = window.GiggleI18n || {};

			/* Image */
			var imgWrap = document.getElementById( 'giggle-modal-image-wrap' );
			var img     = document.getElementById( 'giggle-modal-image' );
			if ( data.imageUrl ) {
				img.src = data.imageUrl;
				img.alt = data.title;
				imgWrap.hidden = false;
			} else {
				imgWrap.hidden = true;
			}

			/* Title */
			document.getElementById( 'giggle-modal-title' ).textContent = data.title;

			/* Dates */
			var datesEl = document.getElementById( 'giggle-modal-dates' );
			datesEl.innerHTML = '';
			if ( data.events && data.events.length ) {
				data.events.forEach( function ( ev ) {
					var span  = document.createElement( 'span' );
					span.className = 'giggle-modal__date';
					var start = fmtDate( ev.startDate );
					var end   = fmtDate( ev.endDate );
					span.textContent = ( end && end !== start ) ? start + ' – ' + end : start;
					datesEl.appendChild( span );
				} );
			}

			/* Meta grid */
			var metaEl = document.getElementById( 'giggle-modal-meta' );
			metaEl.innerHTML = '';

			var groupSize = '';
			if ( data.minParticipants && data.maxParticipants ) {
				groupSize = data.minParticipants + ' – ' + data.maxParticipants + ' ' + ( i18n.persons || 'Personen' );
			} else if ( data.maxParticipants ) {
				groupSize = ( i18n.upTo || 'bis' ) + ' ' + data.maxParticipants + ' ' + ( i18n.persons || 'Personen' );
			} else if ( data.minParticipants ) {
				groupSize = ( i18n.from || 'ab' ) + ' ' + data.minParticipants + ' ' + ( i18n.persons || 'Personen' );
			}

			var durationStr = '';
			if ( data.duration ) {
				durationStr = data.duration + '\u202f' + ( data.durationUnit === 'h' ? ( i18n.hours || 'Std.' ) : ( i18n.minutes || 'Min.' ) );
			}

			[
				[ i18n.location             || 'Ort',            data.location ],
				[ i18n.meetingPoint         || 'Treffpunkt',     data.meetingPoint ],
				[ i18n.registrationDeadline || 'Anmeldeschluss', data.registrationDeadline ? fmtDate( data.registrationDeadline ) : '' ],
				[ i18n.groupSize            || 'Gruppengröße',   groupSize ],
				[ i18n.duration             || 'Dauer',          durationStr ],
			].forEach( function ( row ) {
				if ( ! row[1] ) return;
				var dt = document.createElement( 'dt' );
				dt.className = 'giggle-modal__meta-label';
				dt.textContent = row[0];
				var dd = document.createElement( 'dd' );
				dd.className = 'giggle-modal__meta-value';
				dd.textContent = row[1];
				metaEl.appendChild( dt );
				metaEl.appendChild( dd );
			} );

			metaEl.hidden = ! metaEl.hasChildNodes();

			/* Description — sanitized server-side via wp_kses before encoding */
			document.getElementById( 'giggle-modal-description' ).innerHTML = data.description || '';

			/* CTA */
			document.getElementById( 'giggle-modal-cta' ).href = data.url;

			modal.showModal();
			document.documentElement.style.overflow = 'hidden';
		}

		function closeModal() {
			modal.close();
		}

		modal.addEventListener( 'close', function () {
			document.documentElement.style.overflow = '';
			/* Defer src clear so close animation can finish */
			setTimeout( function () {
				document.getElementById( 'giggle-modal-image' ).src = '';
				document.getElementById( 'giggle-modal-description' ).textContent = '';
			}, 300 );
		} );

		/* Card clicks */
		document.addEventListener( 'click', function ( e ) {
			var card = e.target.closest( '[data-giggle-item]' );
			if ( ! card ) return;
			e.preventDefault();
			try {
				openModal( JSON.parse( card.dataset.giggleItem ) );
			} catch ( err ) {
				/* Malformed JSON — fall back to opening the link */
				window.open( card.href, '_blank', 'noopener,noreferrer' );
			}
		} );

		/* Close button */
		closeBtn.addEventListener( 'click', closeModal );

		/* Backdrop click (clicking the <dialog> element itself, outside the inner panel) */
		modal.addEventListener( 'click', function ( e ) {
			if ( e.target === modal ) closeModal();
		} );
	}

	/* ------------------------------------------------------------------
	   Carousel arrow controls — supports multiple carousels per page
	------------------------------------------------------------------ */

	document.querySelectorAll( '.giggle-events__list' ).forEach( function ( track ) {
		var section = track.closest( '.giggle-events' );
		if ( ! section ) return;

		var prevBtn = section.querySelector( '.giggle-events__arrow--prev' );
		var nextBtn = section.querySelector( '.giggle-events__arrow--next' );
		if ( ! prevBtn || ! nextBtn ) return;

		function scrollAmount() {
			var item = track.querySelector( '.giggle-events__item' );
			if ( ! item ) return track.clientWidth * 0.8;
			var gap       = parseFloat( getComputedStyle( track ).gap ) || 20;
			var slideStep = window.innerWidth >= 1024 ? 3 : 1;
			return ( item.offsetWidth + gap ) * slideStep;
		}

		function updateButtons() {
			var atStart = track.scrollLeft <= 1;
			var atEnd   = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
			prevBtn.classList.toggle( 'is-disabled', atStart );
			prevBtn.disabled = atStart;
			nextBtn.classList.toggle( 'is-disabled', atEnd );
			nextBtn.disabled = atEnd;
		}

		prevBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: -scrollAmount(), behavior: 'smooth' } );
		} );

		nextBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: scrollAmount(), behavior: 'smooth' } );
		} );

		track.addEventListener( 'scroll', updateButtons, { passive: true } );
		requestAnimationFrame( updateButtons );
	} );

} )();
