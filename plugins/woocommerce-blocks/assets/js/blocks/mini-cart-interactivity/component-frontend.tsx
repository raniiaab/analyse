/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { MiniCartDrawer } from './mini-cart-drawer';
import './style.scss';

const renderMiniCartFrontend = () => {
	const onClose = () => {
		document.body.dispatchEvent(
			new Event( 'wc-mini-cart-interactivity-close-drawer' )
		);
	};

	// Check if button is focused. In that case, we want to refocus it after we
	// replace it with the React equivalent.
	let focusedMiniCartBlock: HTMLElement | null = null;
	/* eslint-disable @wordpress/no-global-active-element */
	if (
		document.activeElement &&
		document.activeElement.classList.contains(
			'wc-block-mini-cart__button'
		) &&
		document.activeElement.parentNode instanceof HTMLElement
	) {
		focusedMiniCartBlock = document.activeElement.parentNode;
	}
	/* eslint-enable @wordpress/no-global-active-element */

	renderFrontend( {
		selector: '.wc-block-components-drawer__screen-overlay',
		Block: MiniCartDrawer,
		getProps: ( el ) => {
			const button = document.querySelector(
				'.wc-block-mini-cart__button'
			);
			const dataset =
				button instanceof HTMLButtonElement
					? button.dataset
					: { isInitiallyOpen: 'false' };
			const colorClassNames =
				button instanceof HTMLButtonElement
					? button.classList
							.toString()
							.replace( 'wc-block-mini-cart__button', '' )
					: '';

			const isInitiallyOpen = dataset.isInitiallyOpen === 'true';

			return {
				initialCartTotals: el.dataset.cartTotals
					? JSON.parse( el.dataset.cartTotals )
					: null,
				initialCartItemsCount: el.dataset.cartItemsCount
					? parseInt( el.dataset.cartItemsCount, 10 )
					: 0,
				isInitiallyOpen,
				colorClassNames,
				onClose,
				style: el.dataset.style ? JSON.parse( el.dataset.style ) : {},
				addToCartBehaviour: el.dataset.addToCartBehaviour || 'none',
				hasHiddenPrice: el.dataset.hasHiddenPrice !== 'false',
				contents:
					document.querySelector(
						'.wc-block-mini-cart-interactivity__template-part'
					)?.innerHTML ?? '',
				productCountVisibility: el.dataset.productCountVisibility,
			};
		},
	} );

	// Refocus previously focused button if drawer is not open.
	if (
		focusedMiniCartBlock instanceof HTMLElement &&
		! focusedMiniCartBlock.dataset.isInitiallyOpen
	) {
		const innerButton = focusedMiniCartBlock.querySelector(
			'.wc-block-mini-cart__button'
		);
		if ( innerButton instanceof HTMLElement ) {
			innerButton.focus();
		}
	}
};

renderMiniCartFrontend();
