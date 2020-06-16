{if $siteVerification}
	<meta name="google-site-verification" content="{$siteVerification}" />
{/if}

{if $conversionId}
	<script async src="https://www.googletagmanager.com/gtag/js?id=AW-{$conversionId}"></script>
	<script type="text/javascript">
		window.dataLayer = window.dataLayer || [];
		function gtag() {
			dataLayer.push(arguments);
		}
		gtag('js', new Date());
		gtag('config', 'AW-{$conversionId}');
		console.log("test");
	</script>
{/if}
