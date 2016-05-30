<?php
/*
* 2007-2014 PrestaShop Extension
*/

if (!defined('_PS_VERSION_'))
	exit;

class StatsOrderedProducts extends ModuleGrid
{
	private $html = null;
	private $query = null;
	private $columns = null;
	private $default_sort_column = null;
	private $default_sort_direction = null;
	private $empty_message = null;
	private $paging_message = null;

	public function __construct()
	{
		$this->name = 'statsorderedproducts';
		$this->tab = 'analytics_stats';
		$this->version = '1.2';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

		parent::__construct();

		$this->default_sort_column = 'orderedid';
		$this->default_sort_direction = 'DESC';
		$this->empty_message = $this->l('An empty record-set was returned.');
		$this->paging_message = sprintf($this->l('Displaying %1$s of %2$s'), '{0} - {1}', '{2}');

		$this->columns = array(
			array(
				'id' => 'ordered_id',
				'header' => $this->l('Order ID'),
				'dataIndex' => 'orderedid',
				'align' => 'center'
			),
			array(
				'id' => 'buyer_name',
				'header' => $this->l('Buyer Name'),
				'dataIndex' => 'buyername',
				'align' => 'left'
			),	
			array(
				'id' => 'buyer_email',
				'header' => $this->l('Buyer Email'),
				'dataIndex' => 'buyeremail',
				'align' => 'left'
			),				
			array(
				'id' => 'ordered_langname',
				'header' => $this->l('Product Name'),
				'dataIndex' => 'orderedlangname',
				'align' => 'left'
			),				
			array(
				'id' => 'ordered_name',
				'header' => $this->l('Attributes'),
				'dataIndex' => 'orderedname',
				'align' => 'center'
			),	
			array(
				'id' => 'ordered_quantity',
				'header' => $this->l('Quantity'),
				'dataIndex' => 'orderedquantity',
				'align' => 'center'
			),				
			array(
				'id' => 'ordered_invoice',
				'header' => $this->l('Invoice Date'),
				'dataIndex' => 'orderedinvoice',
				'align' => 'center'
			),
			array(
				'id' => 'ordered_delivery',
				'header' => $this->l('Delivery Date'),
				'dataIndex' => 'ordereddelivery',
				'align' => 'center'
			),			
			array(
				'id' => 'ordered_price',
				'header' => $this->l('Unit Price '),
				'dataIndex' => 'orderedprice',
				'align' => 'right'
			),
			array(
				'id' => 'ordered_total_wtax',
				'header' => $this->l('Total Price'),
				'dataIndex' => 'orderedtotalwtax',
				'align' => 'right'
			),
			array(
				'id' => 'ordered_payment',
				'header' => $this->l('Mode of Payment'),
				'dataIndex' => 'orderedpayment',
				'align' => 'center'
			)			

		);

		$this->displayName = $this->l('Ordered products');
		$this->description = $this->l('Adds a list of the Ordered products to the Stats dashboard.');
		$this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
	}

	public function install()
	{
		return (parent::install() && $this->registerHook('AdminStatsModules'));
	}

	public function hookAdminStatsModules($params)
	{
		$engine_params = array(
			'id' => 'product_id',
			'title' => $this->displayName,
			'columns' => $this->columns,
			'defaultSortColumn' => $this->default_sort_column,
			'defaultSortDirection' => $this->default_sort_direction,
			'emptyMessage' => $this->empty_message,
			'pagingMessage' => $this->paging_message
		);

		if (Tools::getValue('export'))
			$this->csvExport($engine_params);

		$this->html = '
			<div class="panel-heading">
				'.$this->displayName.'
			</div>
			'.$this->engine($engine_params).'
			<a class="btn btn-default export-csv" href="'.htmlentities($_SERVER['REQUEST_URI']).'&export=1">
				<i class="icon-cloud-upload"></i> '.$this->l('CSV Export').'
			</a>';

		return $this->html;
	}

	public function getData()
	{
		$currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		$date_between = $this->getDate();
		$array_date_between = explode(' AND ', $date_between);

		$this->query = 'SELECT SQL_CALC_FOUND_ROWS			
		p.product_name AS orderedname,
		ROUND(p.unit_price_tax_incl,2)  AS orderedprice,
		ROUND(p.total_price_tax_incl,2) AS orderedtotalwtax,
		p.product_quantity AS orderedquantity,
		
		CONCAT(LEFT(cu.`lastname`, 1), \'. \', cu.`firstname`) AS `buyername`,
		cu.email AS buyeremail,
		
		od.reference AS orderedreference, 
		o.id_order AS orderedid, 
		o.invoice_date AS orderedinvoice,
		o.delivery_date AS ordereddelivery,
		o.payment AS orderedpayment,
		pl.name AS orderedlangname
								
		FROM `'._DB_PREFIX_.'order_detail` p

				LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.product_id  = pl.id_product AND pl.id_lang = '.(int)$this->getLang().Shop::addSqlRestrictionOnLang('pl').')
				LEFT JOIN `'._DB_PREFIX_.'product` od ON od.id_product = p.product_id 
				LEFT JOIN `'._DB_PREFIX_.'orders` o ON p.id_order = o.id_order
				LEFT JOIN `'._DB_PREFIX_.'customer` cu ON cu.id_customer = o.id_customer				

				
				WHERE o.invoice_date BETWEEN '.$date_between.'
				';
       
		if (Validate::IsName($this->_sort))
		{
			$this->query .= ' ORDER BY `'.$this->_sort.'`';
			if (isset($this->_direction) && Validate::isSortDirection($this->_direction))
				$this->query .= ' '.$this->_direction;
		}
		
		if (($this->_start === 0 || Validate::IsUnsignedInt($this->_start)) && Validate::IsUnsignedInt($this->_limit))
			$this->query .= ' LIMIT '.$this->_start.', '.($this->_limit);
			
		$values = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($this->query);
		$this->_values = $values;
		$this->_totalCount = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT FOUND_ROWS()');
		
	}
}
