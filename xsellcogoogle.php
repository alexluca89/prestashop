<?php

if (!defined('_PS_VERSION_')) {
	exit;
}


class XsellcoGoogle extends Module
{
	const CLASS_NAME = 'XsellcoGoogle';

	public function __construct($name = null, Context $context = null)
	{
		$this->name = 'xsellcogoogle';
		$this->tab = 'administration';
		$this->version = '1 .0.0';
		$this->author = 'xSellco';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = [
			'min' => '1.6',
			'max' => _PS_VERSION_
		];
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('xSellco Google');
		$this->description = $this->l('Automatically integrates your xSellco Google Shopping channel to your Prestashop webstore.');

		$this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
	}

	public function install()
	{
		if (Shop::isFeatureActive()) {
			Shop::setContext(Shop::CONTEXT_ALL);
		}
		return parent::install()
			&& $this->registerHook('displayHeader')
			&& $this->registerHook('displayFooterProduct');
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	private function getConversionData($token)
	{
		$url = 'https://api.xsellco.com/google-shopping/plugin-config?token=' . $token;
		$options = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING => "",
			CURLOPT_USERAGENT => "spider",
			CURLOPT_AUTOREFERER => true,
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT => 120,
			CURLOPT_MAXREDIRS => 10,
		);

		$ch = curl_init($url);
		curl_setopt_array($ch, $options);
		$content = curl_exec($ch);
		curl_close($ch);

		return json_decode($content, true);
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name)) {
			$xsellcoGoogleToken = strval(Tools::getValue('xsellco_gs_token'));

			if (
				empty($xsellcoGoogleToken) ||
				!Validate::isGenericName($xsellcoGoogleToken)
			) {
				$output .= $this->displayError($this->l('Invalid token!'));
				return $output.$this->displayForm();
			}
			$conversionData = $this->getConversionData($xsellcoGoogleToken);
			if (!$conversionData || !empty($conversionData['error'])) {
				$output .= $this->displayError($this->l('Invalid token!'));
				return $output.$this->displayForm();
			}

			Configuration::updateValue('xsellco_gs_token', $xsellcoGoogleToken);
			Configuration::updateValue('site_verification', $conversionData['site_verification']);
			Configuration::updateValue('conversion_id', $conversionData['adwords_conversion_id']);
			Configuration::updateValue('conversion_label', $conversionData['adwords_conversion_label']);
			$output .= $this->displayConfirmation($this->l('Successfully connected to ' . $conversionData['client_name'] . ' / ' . $conversionData['channel_name']));
		}

		return $output.$this->displayForm();
	}

	public function displayForm()
	{
		// Get default language
		$defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

		// Init Fields form array
		$fieldsForm[0]['form'] = [
			'legend' => [
				'title' => $this->l('Settings'),
			],
			'input' => [
				[
					'type' => 'text',
					'label' => $this->l('Configuration value'),
					'name' => 'xsellco_gs_token',
					'size' => 20,
					'required' => true
				]
			],
			'submit' => [
				'title' => $this->l('Save'),
				'class' => 'btn btn-default pull-right'
			]
		];

		$helper = new HelperForm();

		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

		// Language
		$helper->default_form_language = $defaultLang;
		$helper->allow_employee_form_lang = $defaultLang;

		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;        // false -> remove toolbar
		$helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = [
			'save' => [
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
					'&token='.Tools::getAdminTokenLite('AdminModules'),
			],
			'back' => [
				'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
				'desc' => $this->l('Back to list')
			]
		];

		// Load current value
		$helper->fields_value['xsellco_gs_token'] = Tools::getValue('xsellco_gs_token', Configuration::get('xsellco_gs_token'));

		return $helper->generateForm($fieldsForm);
	}

	public function hookDisplayHeader()
	{
		$this->context->smarty->assign(
			array(
				'siteVerification' => Configuration::get('site_verification'),
				'conversionId' => Configuration::get('conversion_id')
			)
		);
		return $this->display(__FILE__, 'gtag_general.tpl');
	}

	public function hookDisplayFooterProduct()
	{
		$product = new Product(Tools::getValue('id_product'));
		if (!$product) {
			return;
		}

		$this->context->smarty->assign(
			array(
				'conversionId' => Configuration::get('conversion_id'),
				'productPrice' => $product->getPrice(),
				'productId' => $product->id
			)
		);
		return $this->display(__FILE__, 'gtag_product.tpl');
	}
}