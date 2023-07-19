/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { Dropdown } from '@wordpress/components';
import * as Woo from '@woocommerce/components';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './index.scss';

const MyExamplePage = () => (
	<Fragment>
		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Search', 'hbc-payment-woocommerce')}
			/>
			<Woo.Search
				type="products"
				placeholder="Search for something"
				selected={[]}
				onChange={(items) => setInlineSelect(items)}
				inlineTags
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Dropdown', 'hbc-payment-woocommerce')}
			/>
			<Dropdown
				renderToggle={({ isOpen, onToggle }) => (
					<Woo.DropdownButton
						onClick={onToggle}
						isOpen={isOpen}
						labels={['Dropdown']}
					/>
				)}
				renderContent={() => <p>Dropdown content here</p>}
			/>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Pill shaped container', 'hbc-payment-woocommerce')}
			/>
			<Woo.Pill className={'pill'}>
				{__('Pill Shape Container', 'hbc-payment-woocommerce')}
			</Woo.Pill>
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Spinner', 'hbc-payment-woocommerce')}
			/>
			<Woo.H>I am a spinner!</Woo.H>
			<Woo.Spinner />
		</Woo.Section>

		<Woo.Section component="article">
			<Woo.SectionHeader
				title={__('Datepicker', 'hbc-payment-woocommerce')}
			/>
			<Woo.DatePicker
				text={__('I am a datepicker!', 'hbc-payment-woocommerce')}
				dateFormat={'MM/DD/YYYY'}
			/>
		</Woo.Section>
	</Fragment>
);

addFilter(
	'woocommerce_admin_pages_list',
	'hbc-payment-woocommerce',
	(pages) => {
		pages.push({
			container: MyExamplePage,
			path: '/hbc-payment-woocommerce',
			breadcrumbs: [
				__('Hbc Payment Woocommerce', 'hbc-payment-woocommerce'),
			],
			navArgs: {
				id: 'hbc_payment_woocommerce',
			},
		});

		return pages;
	}
);
