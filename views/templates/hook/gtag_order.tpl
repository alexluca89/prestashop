{if $conversionId}
	<script type="text/javascript">
		gtag('event', 'conversion', {
			'send_to': 'AW-{$conversionId}/{$conversionLabel}',
			'value': {$orderTotal},
			'currency': '{$orderCurrency}',
			'transaction_id': '{$orderId}'
		});
		gtag('event', 'page_view', {
			'send_to': 'AW-{$conversionId}',
			'ecomm_pagetype': 'purchase',
			'ecomm_prodid': {$productIds},
			'ecomm_totalvalue': {$orderTotal}
		});
	</script>
{/if}